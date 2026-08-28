'use strict';

require('dotenv').config();
const express = require('express');
const QRCode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.disable('x-powered-by');
app.use(express.json({limit: '256kb'}));

// Deliberately local-only. This API must never listen on 0.0.0.0 or the
// public web interface. CI4 reaches it from the same server via 127.0.0.1.
const host = '127.0.0.1';
const port = Number(process.env.PORT || 3099);
const key = String(process.env.BRIDGE_KEY || '').trim();
const country = process.env.DEFAULT_COUNTRY_CODE || '91';

if (!key) {
    console.error('BRIDGE_KEY is required. Refusing to start an unauthenticated local bridge.');
    process.exit(1);
}

let latestQr = null;
let ready = false;
let info = null;
let lastError = null;
let shuttingDown = false;

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: process.env.SESSION_ID || 'mobile-shop-main',
        dataPath: process.env.SESSION_PATH || './sessions'
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    }
});

client.on('qr', qr => {
    latestQr = qr;
    ready = false;
    lastError = null;
    console.log('WhatsApp QR generated');
});

client.on('ready', () => {
    ready = true;
    latestQr = null;
    info = client.info ? {
        pushname: client.info.pushname,
        wid: client.info.wid?._serialized
    } : null;
    lastError = null;
    console.log('WhatsApp ready');
});

client.on('authenticated', () => {
    lastError = null;
    console.log('WhatsApp authenticated');
});

client.on('auth_failure', msg => {
    ready = false;
    lastError = String(msg);
    console.error('Auth failure:', msg);
});

client.on('disconnected', reason => {
    ready = false;
    info = null;
    lastError = 'Disconnected: ' + String(reason);
    console.error(lastError);
});

function guard(req, res, next) {
    if (req.get('X-Bridge-Key') !== key) {
        return res.status(401).json({ok: false, error: 'Unauthorized'});
    }
    next();
}

function normalizePhone(value) {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.length === 10) digits = country + digits;
    return digits;
}

app.use(guard);

app.get('/status', (req, res) => {
    res.json({
        ok: true,
        ready,
        info,
        lastError,
        localOnly: true,
        pid: process.pid,
        uptime: Math.floor(process.uptime())
    });
});

app.get('/qr', async (req, res) => {
    if (ready) return res.json({ok: true, ready: true, qrDataUrl: null});
    if (!latestQr) return res.json({ok: true, ready: false, qrDataUrl: null});

    try {
        const qrDataUrl = await QRCode.toDataURL(latestQr, {margin: 1, width: 320});
        res.json({ok: true, ready: false, qrDataUrl});
    } catch (e) {
        res.status(500).json({ok: false, error: e.message || String(e)});
    }
});

app.post('/send', async (req, res) => {
    try {
        if (!ready) return res.status(409).json({ok: false, error: 'WhatsApp is not connected'});

        const phone = normalizePhone(req.body.phone);
        const message = String(req.body.message || '').trim();
        if (!phone || !message) return res.status(422).json({ok: false, error: 'phone and message are required'});

        const chatId = phone + '@c.us';
        const exists = await client.isRegisteredUser(chatId);
        if (!exists) return res.status(422).json({ok: false, error: 'Number is not registered on WhatsApp'});

        const sent = await client.sendMessage(chatId, message);
        res.json({ok: true, messageId: sent.id?._serialized || null});
    } catch (e) {
        res.status(500).json({ok: false, error: e.message || String(e)});
    }
});

app.post('/logout', async (req, res) => {
    try {
        await client.logout();
        ready = false;
        latestQr = null;
        info = null;
        res.json({ok: true});
    } catch (e) {
        res.status(500).json({ok: false, error: e.message || String(e)});
    }
});

const server = app.listen(port, host, () => {
    console.log(`WhatsApp local bridge listening only on http://${host}:${port}`);
});

async function shutdown(signal) {
    if (shuttingDown) return;
    shuttingDown = true;
    console.log(`Received ${signal}; shutting down WhatsApp bridge`);
    server.close();
    try { await client.destroy(); } catch (_) {}
    process.exit(0);
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

client.initialize().catch(e => {
    lastError = e.message || String(e);
    console.error('WhatsApp initialization failed:', e);
});
