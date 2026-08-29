'use strict';

require('dotenv').config();

const express = require('express');
const QRCode = require('qrcode');
const fs = require('fs');
const os = require('os');
const path = require('path');
const crypto = require('crypto');
const { execFile } = require('child_process');

const {
    Client,
    LocalAuth,
    MessageMedia
} = require('whatsapp-web.js');

const app = express();

app.disable('x-powered-by');

app.use(express.json({
    limit: '20mb'
}));

/*
 * LOCAL ONLY
 *
 * CI4 and this gateway are on the same server.
 *
 * Chromium also runs locally.
 *
 * Chromium NEVER needs to open the protected
 * drmi.vismrit.tech invoice URL.
 */
const host = '127.0.0.1';

const port = Number(
    process.env.PORT || 3099
);

const key = String(
    process.env.BRIDGE_KEY || ''
).trim();

const country = String(
    process.env.DEFAULT_COUNTRY_CODE || '91'
).replace(/\D/g, '');

const chromiumPath =
    String(
        process.env.CHROMIUM_PATH ||
        '/snap/bin/chromium'
    ).trim();

if (!key) {

    console.error(
        'BRIDGE_KEY is required. ' +
        'Refusing to start an unauthenticated bridge.'
    );

    process.exit(1);
}

let latestQr = null;
let ready = false;
let info = null;
let lastError = null;
let shuttingDown = false;


const client = new Client({

    authStrategy: new LocalAuth({

        clientId:
            process.env.SESSION_ID ||
            'mobile-shop-main',

        dataPath:
            process.env.SESSION_PATH ||
            './sessions'
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

    console.log(
        'WhatsApp QR generated'
    );
});


client.on('ready', () => {

    ready = true;

    latestQr = null;

    info = client.info
        ? {
            pushname:
                client.info.pushname,

            wid:
                client.info.wid?._serialized
        }
        : null;

    lastError = null;

    console.log(
        'WhatsApp ready'
    );
});


client.on('authenticated', () => {

    lastError = null;

    console.log(
        'WhatsApp authenticated'
    );
});


client.on('auth_failure', msg => {

    ready = false;

    lastError = String(msg);

    console.error(
        'Auth failure:',
        msg
    );
});


client.on('disconnected', reason => {

    ready = false;

    info = null;

    lastError =
        'Disconnected: ' +
        String(reason);

    console.error(
        lastError
    );
});


function guard(req, res, next) {

    if (
        req.get('X-Bridge-Key') !== key
    ) {

        return res
            .status(401)
            .json({
                ok: false,
                error: 'Unauthorized'
            });
    }

    next();
}


function normalizePhone(value) {

    let digits = String(value || '')
        .replace(/\D/g, '');

    if (
        digits.length === 10 &&
        country
    ) {
        digits = country + digits;
    }

    return digits;
}


/*
 * Convert COMPLETE HTML to PDF.
 *
 * Chromium receives a local temporary HTML file.
 *
 * It DOES NOT visit:
 *
 * https://drmi.vismrit.tech/...
 *
 * Therefore there is no admin authentication problem.
 */
function htmlToPdf(html) {

    return new Promise(
        (resolve, reject) => {

            if (
                typeof html !== 'string' ||
                html.trim() === ''
            ) {

                return reject(
                    new Error(
                        'HTML is empty.'
                    )
                );
            }

            if (
                !fs.existsSync(
                    chromiumPath
                )
            ) {

                return reject(
                    new Error(
                        'Chromium not found at: ' +
                        chromiumPath
                    )
                );
            }

            const id =
                crypto.randomBytes(12)
                    .toString('hex');

            const tempDir =
                fs.mkdtempSync(
                    path.join(
                        os.tmpdir(),
                        'invoice-pdf-'
                    )
                );

            const htmlPath =
                path.join(
                    tempDir,
                    `invoice-${id}.html`
                );

            const pdfPath =
                path.join(
                    tempDir,
                    `invoice-${id}.pdf`
                );

            try {

                fs.writeFileSync(
                    htmlPath,
                    html,
                    'utf8'
                );

            } catch (e) {

                try {
                    fs.rmSync(
                        tempDir,
                        {
                            recursive: true,
                            force: true
                        }
                    );
                } catch (_) {}

                return reject(
                    new Error(
                        'Unable to create temporary HTML: ' +
                        e.message
                    )
                );
            }

            /*
             * Chromium Snap is explicitly used:
             *
             * /snap/bin/chromium
             *
             * --headless=new
             * --no-sandbox
             * --disable-gpu
             * --disable-dev-shm-usage
             * --allow-file-access-from-files
             *
             * The input is file:// HTML.
             */
            const args = [

                '--headless=new',

                '--no-sandbox',

                '--disable-setuid-sandbox',

                '--disable-gpu',

                '--disable-dev-shm-usage',

                '--disable-software-rasterizer',

                '--allow-file-access-from-files',

                '--disable-extensions',

                '--disable-background-networking',

                '--disable-sync',

                '--no-first-run',

                '--no-default-browser-check',

                '--run-all-compositor-stages-before-draw',

                `--print-to-pdf=${pdfPath}`,

                '--print-to-pdf-no-header',

                `file://${htmlPath}`
            ];

            console.log(
                'Generating invoice PDF with Chromium:',
                chromiumPath
            );

            execFile(
                chromiumPath,
                args,
                {
                    timeout: 120000,
                    maxBuffer: 1024 * 1024 * 4
                },
                (error, stdout, stderr) => {

                    if (error) {

                        const errText =
                            String(stderr || '')
                                .trim();

                        try {
                            fs.rmSync(
                                tempDir,
                                {
                                    recursive: true,
                                    force: true
                                }
                            );
                        } catch (_) {}

                        return reject(
                            new Error(
                                'Chromium PDF generation failed: ' +
                                error.message +
                                (
                                    errText
                                        ? ' | ' + errText
                                        : ''
                                )
                            )
                        );
                    }

                    if (
                        !fs.existsSync(
                            pdfPath
                        )
                    ) {

                        try {
                            fs.rmSync(
                                tempDir,
                                {
                                    recursive: true,
                                    force: true
                                }
                            );
                        } catch (_) {}

                        return reject(
                            new Error(
                                'Chromium finished but PDF file was not created.'
                            )
                        );
                    }

                    try {

                        const pdfData =
                            fs.readFileSync(
                                pdfPath
                            );

                        if (
                            !pdfData ||
                            pdfData.length < 100
                        ) {

                            throw new Error(
                                'Generated PDF is empty or invalid.'
                            );
                        }

                        /*
                         * Keep the path until the caller
                         * has finished using the data.
                         */
                        resolve({

                            path: pdfPath,

                            data:
                                pdfData.toString(
                                    'base64'
                                ),

                            size:
                                pdfData.length
                        });

                    } catch (e) {

                        try {
                            fs.rmSync(
                                tempDir,
                                {
                                    recursive: true,
                                    force: true
                                }
                            );
                        } catch (_) {}

                        reject(
                            new Error(
                                'Unable to read generated PDF: ' +
                                e.message
                            )
                        );
                    }
                }
            );
        }
    );
}


app.use(guard);


/*
 * STATUS
 */
app.get(
    '/status',
    (req, res) => {

        res.json({

            ok: true,

            ready,

            info,

            lastError,

            localOnly: true,

            chromiumPath,

            chromiumExists:
                fs.existsSync(
                    chromiumPath
                ),

            pid: process.pid,

            uptime:
                Math.floor(
                    process.uptime()
                )
        });
    }
);


/*
 * QR
 */
app.get(
    '/qr',
    async (req, res) => {

        if (ready) {

            return res.json({
                ok: true,
                ready: true,
                qrDataUrl: null
            });
        }

        if (!latestQr) {

            return res.json({
                ok: true,
                ready: false,
                qrDataUrl: null
            });
        }

        try {

            const qrDataUrl =
                await QRCode.toDataURL(
                    latestQr,
                    {
                        margin: 1,
                        width: 320
                    }
                );

            res.json({
                ok: true,
                ready: false,
                qrDataUrl
            });

        } catch (e) {

            res.status(500).json({
                ok: false,
                error:
                    e.message ||
                    String(e)
            });
        }
    }
);


/*
 * HTML -> PDF
 *
 * CI4 sends complete invoice HTML here.
 */
app.post(
    '/html-to-pdf',
    async (req, res) => {

        try {

            const html =
                String(
                    req.body?.html || ''
                );

            if (!html.trim()) {

                return res
                    .status(422)
                    .json({
                        ok: false,
                        error:
                            'html is required'
                    });
            }

            /*
             * Limit to avoid accidentally
             * processing a huge request.
             */
            if (
                Buffer.byteLength(
                    html,
                    'utf8'
                ) > 15 * 1024 * 1024
            ) {

                return res
                    .status(422)
                    .json({
                        ok: false,
                        error:
                            'HTML is too large'
                    });
            }

            const pdf =
                await htmlToPdf(html);

            res.json({

                ok: true,

                filename:
                    'invoice.pdf',

                mimetype:
                    'application/pdf',

                size:
                    pdf.size,

                data:
                    pdf.data
            });

            /*
             * Remove temporary files after
             * the PDF has already been converted
             * to base64 and returned.
             */
            if (
                pdf.path &&
                fs.existsSync(
                    pdf.path
                )
            ) {

                const dir =
                    path.dirname(
                        pdf.path
                    );

                try {

                    fs.rmSync(
                        dir,
                        {
                            recursive: true,
                            force: true
                        }
                    );

                } catch (_) {}
            }

        } catch (e) {

            console.error(
                'HTML-to-PDF error:',
                e
            );

            res.status(500).json({

                ok: false,

                error:
                    e.message ||
                    String(e)
            });
        }
    }
);


/*
 * SEND TEXT / MEDIA
 */
app.post(
    '/send',
    async (req, res) => {

        try {

            if (!ready) {

                return res
                    .status(409)
                    .json({
                        ok: false,
                        error:
                            'WhatsApp is not connected'
                    });
            }

            const phone =
                normalizePhone(
                    req.body.phone
                );

            const message =
                String(
                    req.body.message || ''
                ).trim();

            const media =
                req.body.media &&
                typeof req.body.media === 'object'
                    ? req.body.media
                    : null;

            if (!phone) {

                return res
                    .status(422)
                    .json({
                        ok: false,
                        error:
                            'phone is required'
                    });
            }

            if (
                !message &&
                !media
            ) {

                return res
                    .status(422)
                    .json({
                        ok: false,
                        error:
                            'message or media is required'
                    });
            }

            if (media) {

                const data =
                    String(
                        media.data || ''
                    );

                const mimetype =
                    String(
                        media.mimetype ||
                        'application/octet-stream'
                    );

                const filename =
                    String(
                        media.filename ||
                        'document'
                    );

                if (!data) {

                    return res
                        .status(422)
                        .json({
                            ok: false,
                            error:
                                'Media data is empty'
                        });
                }

                /*
                 * Base64 limit.
                 */
                if (
                    data.length >
                    18000000
                ) {

                    return res
                        .status(422)
                        .json({
                            ok: false,
                            error:
                                'Media is too large'
                        });
                }
            }

            const chatId =
                phone + '@c.us';

            const exists =
                await client.isRegisteredUser(
                    chatId
                );

            if (!exists) {

                return res
                    .status(422)
                    .json({
                        ok: false,
                        error:
                            'Number is not registered on WhatsApp'
                    });
            }

            let sent;

            if (media) {

                const mm =
                    new MessageMedia(
                        String(
                            media.mimetype ||
                            'application/octet-stream'
                        ),

                        String(
                            media.data
                        ),

                        String(
                            media.filename ||
                            'document'
                        )
                    );

                const options = {};

                if (message) {
                    options.caption =
                        message;
                }

                sent =
                    await client.sendMessage(
                        chatId,
                        mm,
                        options
                    );

            } else {

                sent =
                    await client.sendMessage(
                        chatId,
                        message
                    );
            }

            res.json({

                ok: true,

                messageId:
                    sent.id?._serialized ||
                    null
            });

        } catch (e) {

            console.error(
                'WhatsApp send error:',
                e
            );

            res.status(500).json({

                ok: false,

                error:
                    e.message ||
                    String(e)
            });
        }
    }
);


/*
 * LOGOUT
 */
app.post(
    '/logout',
    async (req, res) => {

        try {

            await client.logout();

            ready = false;

            latestQr = null;

            info = null;

            res.json({
                ok: true
            });

        } catch (e) {

            res.status(500).json({
                ok: false,
                error:
                    e.message ||
                    String(e)
            });
        }
    }
);


const server =
    app.listen(
        port,
        host,
        () => {

            console.log(
                `WhatsApp local bridge listening only on http://${host}:${port}`
            );

            console.log(
                `Chromium path: ${chromiumPath}`
            );

            console.log(
                `Chromium exists: ${fs.existsSync(chromiumPath)}`
            );
        }
    );


async function shutdown(signal) {

    if (shuttingDown) {
        return;
    }

    shuttingDown = true;

    console.log(
        `Received ${signal}; shutting down WhatsApp bridge`
    );

    server.close();

    try {
        await client.destroy();
    } catch (_) {}

    process.exit(0);
}


process.on(
    'SIGTERM',
    () => shutdown('SIGTERM')
);

process.on(
    'SIGINT',
    () => shutdown('SIGINT')
);


client.initialize().catch(e => {

    lastError =
        e.message ||
        String(e);

    console.error(
        'WhatsApp initialization failed:',
        e
    );
});
