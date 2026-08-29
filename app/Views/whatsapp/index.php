<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="ms-page-head">
    <div>
        <h1>WhatsApp Web</h1>
        <p>The browser talks only to this CI4 application. CI4 talks privately to the WhatsApp service on 127.0.0.1 of this server.</p>
    </div>
    <button class="ms-btn ms-btn-secondary" type="button" onclick="loadStatus(true)">Refresh</button>
</div>

<div class="ms-two-col">
    <div class="ms-card">
        <h2 class="ms-section-title">Connection</h2>
        <div id="waStatus" class="ms-muted">Checking local server service...</div>
        <div id="waError" class="ms-help ms-spacer-top"></div>
        <div id="qrWrap" class="ms-spacer-top"></div>
    </div>

    <div class="ms-card">
        <h2 class="ms-section-title">Send test</h2>
        <form id="waTest">
            <div class="ms-field">
                <label>Phone with country code</label>
                <input class="ms-input" name="phone" placeholder="9198XXXXXXXX" required>
            </div>
            <div class="ms-field ms-spacer-top">
                <label>Message</label>
                <textarea class="ms-textarea" name="message" required>Test message from MobileShop ERP</textarea>
            </div>
            <button class="ms-btn ms-btn-primary ms-spacer-top">Send test</button>
        </form>
        <div id="testResult" class="ms-help"></div>
    </div>
</div>

<div class="ms-card ms-spacer-top">
    <h2 class="ms-section-title">Server-local architecture</h2>
    <p class="ms-muted">The WhatsApp Node process binds only to <code>127.0.0.1</code>. It is not exposed on your domain and is not reachable from a customer's computer. Invoice messages and reminders are sent by PHP/CLI on the server through that local service.</p>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let waCsrfHash = '<?= csrf_hash() ?>';
let waBusy = false;

function text(el, value) {
    el.textContent = value || '';
}

async function loadStatus(showToast = false) {
    if (waBusy) return;
    waBusy = true;

    const statusEl = document.getElementById('waStatus');
    const errorEl  = document.getElementById('waError');
    const qrWrap   = document.getElementById('qrWrap');

    try {
        const response = await fetch('<?= site_url('whatsapp/status') ?>', {
            headers: {'Accept': 'application/json'},
            cache: 'no-store'
        });
        const state = await response.json();

        qrWrap.replaceChildren();
        text(errorEl, '');

        if (!state.bridgeAvailable) {
            statusEl.innerHTML = '<span class="ms-badge is-danger">Local service offline</span>';
            text(errorEl, state.error || 'The server-local WhatsApp service is not running.');
            if (showToast && typeof shopToast === 'function') shopToast('Local WhatsApp service is offline', 'error');
            return;
        }

        if (state.ready) {
            statusEl.innerHTML = '<span class="ms-badge is-ok">Connected</span>';
            if (state.info && state.info.pushname) {
                statusEl.append(document.createTextNode(' ' + state.info.pushname));
            }
            if (showToast && typeof shopToast === 'function') shopToast('WhatsApp is connected', 'ok');
            return;
        }

        statusEl.innerHTML = '<span class="ms-badge is-warn">Waiting for WhatsApp login</span>';
        if (state.lastError) text(errorEl, state.lastError);

        // QR is requested only after CI confirms its localhost bridge is alive.
        const qrResponse = await fetch('<?= site_url('whatsapp/qr') ?>', {
            headers: {'Accept': 'application/json'},
            cache: 'no-store'
        });
        const qr = await qrResponse.json();

        if (qr.qrDataUrl) {
            const image = document.createElement('img');
            image.alt = 'WhatsApp QR';
            image.src = qr.qrDataUrl;
            image.className = 'ms-qr';
            qrWrap.appendChild(image);

            const help = document.createElement('p');
            help.className = 'ms-muted';
            help.textContent = 'Scan from WhatsApp → Linked devices.';
            qrWrap.appendChild(help);
        } else {
            const waiting = document.createElement('p');
            waiting.className = 'ms-muted';
            waiting.textContent = 'Local service is starting. Waiting for QR...';
            qrWrap.appendChild(waiting);
        }
    } catch (e) {
        statusEl.innerHTML = '<span class="ms-badge is-danger">Status error</span>';
        text(errorEl, e && e.message ? e.message : 'Could not read WhatsApp status.');
        qrWrap.replaceChildren();
    } finally {
        waBusy = false;
    }
}

document.getElementById('waTest').addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(event.target);
    fd.append('<?= csrf_token() ?>', waCsrfHash);

    try {
        const response = await fetch('<?= site_url('whatsapp/send-test') ?>', {method: 'POST', body: fd});
        const result = await response.json();
        if (result.csrfHash) waCsrfHash = result.csrfHash;
        document.getElementById('testResult').textContent = result.ok ? 'Sent successfully' : (result.error || 'Failed');
        if (typeof shopToast === 'function') shopToast(result.ok ? 'WhatsApp test sent' : (result.error || 'WhatsApp send failed'), result.ok ? 'ok' : 'error');
    } catch (e) {
        document.getElementById('testResult').textContent = e.message || 'Request failed';
    }
});

loadStatus(false);
setInterval(() => loadStatus(false), 15000);
</script>
<?= $this->endSection() ?>
