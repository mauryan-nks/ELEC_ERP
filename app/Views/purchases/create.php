<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="ms-page-head">
    <div>
        <h1>Receive purchase</h1>
        <p>
            Add products, scan barcodes/QR codes, and scan IMEI or serial numbers
            for individual inventory units.
        </p>
    </div>

    <a
        class="ms-btn ms-btn-secondary"
        href="<?= site_url('purchases') ?>"
    >
        Purchase history
    </a>
</div>


<form
    method="post"
    action="<?= site_url('purchases') ?>"
    id="purchaseForm"
>
    <?= csrf_field() ?>


    <!-- =========================================================
         PURCHASE DETAILS
         ========================================================= -->

    <div class="ms-card">

        <div class="ms-form-grid">

            <div class="ms-field">

                <label>Supplier</label>

                <div class="ms-actions ms-nowrap">

                    <select
                        class="ms-select"
                        id="supplierSelect"
                        name="supplier_id"
                    >
                        <option value="">
                            Unspecified supplier
                        </option>

                        <?php foreach ($suppliers as $s): ?>

                            <option value="<?= $s['id'] ?>">
                                <?= esc($s['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        class="ms-btn ms-btn-secondary"
                        type="button"
                        data-open-dialog="supplierQuickDialog"
                    >
                        + Add
                    </button>

                </div>

            </div>


            <div class="ms-field">

                <label>Supplier invoice no.</label>

                <input
                    class="ms-input"
                    name="supplier_invoice_no"
                >

            </div>


            <div class="ms-field">

                <label>Purchase date</label>

                <input
                    class="ms-input"
                    type="date"
                    name="purchase_date"
                    value="<?= date('Y-m-d') ?>"
                >

            </div>


            <div class="ms-field">

                <label>Amount paid now</label>

                <input
                    class="ms-input"
                    type="number"
                    step="0.01"
                    min="0"
                    name="paid_amount"
                    value="0"
                >

            </div>


            <div class="ms-field">

                <label>Payment method</label>

                <select
                    class="ms-select"
                    name="payment_method"
                >
                    <option value="cash">cash</option>
                    <option value="upi">upi</option>
                    <option value="card">card</option>
                    <option value="bank">bank</option>
                    <option value="credit">credit</option>
                    <option value="other">other</option>
                </select>

            </div>


            <div class="ms-field">

                <label>Payment reference</label>

                <input
                    class="ms-input"
                    name="payment_reference"
                >

            </div>


            <div class="ms-field ms-full">

                <label>Internal purchase notes</label>

                <textarea
                    class="ms-textarea"
                    name="notes"
                ></textarea>

            </div>

        </div>

    </div>


    <!-- =========================================================
         PURCHASE ITEMS HEADER
         ========================================================= -->

    <div class="ms-page-head ms-spacer-top">

        <div>

            <h2 class="ms-section-title">
                Purchase items
            </h2>

            <p>
                Scan an accessory barcode/SKU to add the product,
                or select a tracked product and scan its IMEI/serial.
            </p>

        </div>


        <div class="ms-actions ms-nowrap">

            <button
                class="ms-btn ms-btn-secondary"
                type="button"
                id="globalScanButton"
            >
                📷 Scan
            </button>


            <button
                class="ms-btn ms-btn-secondary"
                type="button"
                onclick="window.addPurchaseRow()"
            >
                + Add item
            </button>

        </div>

    </div>


    <!-- =========================================================
         SCAN STATUS
         ========================================================= -->

    <div
        id="scanStatus"
        class="ms-card"
        style="
            display:none;
            margin-bottom:12px;
        "
    ></div>


    <!-- =========================================================
         PURCHASE ROWS
         ========================================================= -->

    <div id="purchaseRows"></div>


    <!-- =========================================================
         SAVE
         ========================================================= -->

    <div class="ms-card">

        <button
            class="ms-btn ms-btn-primary"
            type="submit"
        >
            Save purchase & receive stock
        </button>

    </div>

</form>


<!-- =============================================================
     SUPPLIER DIALOG
     ============================================================= -->

<dialog id="supplierQuickDialog">

    <div class="ms-dialog-body">

        <div class="ms-dialog-head">

            <h3>
                Add supplier/source
            </h3>

            <button
                class="ms-btn ms-btn-secondary is-sm"
                type="button"
                data-close-dialog
            >
                Close
            </button>

        </div>


        <form id="quickSupplier">

            <div class="ms-form-grid">

                <div class="ms-field">

                    <label>Name *</label>

                    <input
                        class="ms-input"
                        name="name"
                        required
                    >

                </div>


                <div class="ms-field">

                    <label>Type</label>

                    <select
                        class="ms-select"
                        name="supplier_type"
                    >
                        <option value="vendor">
                            Vendor
                        </option>

                        <option value="other_store">
                            Other store
                        </option>

                        <option value="individual">
                            Individual
                        </option>
                    </select>

                </div>


                <div class="ms-field">

                    <label>Phone</label>

                    <input
                        class="ms-input"
                        name="phone"
                    >

                </div>


                <div class="ms-field">

                    <label>GSTIN</label>

                    <input
                        class="ms-input"
                        name="gstin"
                    >

                </div>


                <div class="ms-field ms-full">

                    <button
                        class="ms-btn ms-btn-primary"
                        type="submit"
                    >
                        Add Supplier
                    </button>

                </div>

            </div>

        </form>

    </div>

</dialog>


<!-- =============================================================
     SCANNER DIALOG
     ============================================================= -->

<dialog
    id="purchaseScannerDialog"
    style="
        width:min(620px,94vw);
        max-width:620px;
        padding:0;
    "
>

    <div class="ms-dialog-body">

        <div class="ms-dialog-head">

            <div>

                <h3>
                    Scan barcode / QR / IMEI
                </h3>

                <div
                    class="ms-muted"
                    id="scannerHint"
                >
                    Select a camera and scan.
                </div>

            </div>


            <button
                class="ms-btn ms-btn-secondary is-sm"
                type="button"
                id="closeScannerButton"
            >
                Close
            </button>

        </div>


        <!-- CAMERA SELECT -->

        <div class="ms-field">

            <label>
                Camera
            </label>

            <select
                class="ms-select"
                id="cameraSelect"
            >
                <option value="">
                    Detecting cameras...
                </option>
            </select>

        </div>


        <!-- CAMERA CONTROLS -->

        <div
            class="ms-actions"
            style="
                display:flex;
                flex-wrap:wrap;
                gap:7px;
                margin-bottom:10px;
            "
        >

            <button
                type="button"
                class="ms-btn ms-btn-secondary"
                id="previousCameraButton"
            >
                ◀ Previous
            </button>


            <button
                type="button"
                class="ms-btn ms-btn-secondary"
                id="switchCameraButton"
            >
                🔄 Switch
            </button>


            <button
                type="button"
                class="ms-btn ms-btn-secondary"
                id="nextCameraButton"
            >
                Next ▶
            </button>

        </div>


        <!-- CAMERA PREVIEW -->

        <div
            id="scannerPreviewBox"
            style="
                width:min(100%,460px);
                height:230px;
                margin:10px auto 14px;
                border:3px solid transparent;
                border-radius:12px;
                overflow:hidden;
                background:#111;
                position:relative;
                transition:
                    border-color .15s ease,
                    background-color .15s ease;
            "
        >

            <div
                id="purchaseQrReader"
                style="
                    width:100%;
                    height:100%;
                "
            ></div>

        </div>


        <!-- USB / BLUETOOTH -->

        <div class="ms-field">

            <label>
                USB / Bluetooth scanner or manual entry
            </label>

            <input
                id="scannerManualInput"
                class="ms-input"
                type="text"
                autocomplete="off"
                placeholder="Scan code and press Enter"
            >

        </div>


        <div
            id="scannerResult"
            class="ms-help"
            style="
                margin-top:10px;
                min-height:20px;
            "
        ></div>

    </div>

</dialog>


<!-- =============================================================
     PRODUCT ROW TEMPLATE
     ============================================================= -->

<template id="purchaseRowTemplate">

    <div class="ms-item-card purchase-row">

        <div class="ms-item-grid">

            <div class="ms-field">

                <label>
                    Product *
                </label>

                <select
                    class="ms-select product-select"
                    required
                >

                    <option value="">
                        Select product
                    </option>


                    <?php foreach ($products as $p): ?>

                        <option
                            value="<?= $p['id'] ?>"
                            data-serialized="<?= (int)$p['is_serialized'] ?>"
                            data-tax="<?= esc($p['tax_percent']) ?>"
                            data-sku="<?= esc($p['sku'] ?? '') ?>"
                        >

                            <?= esc(
                                trim(
                                    ($p['brand_name'] ?? '') .
                                    ' ' .
                                    $p['name'] .
                                    ' ' .
                                    ($p['model'] ?? '')
                                )
                            ) ?>

                            <?php if ($p['is_serialized']): ?>
                                · tracked
                            <?php endif; ?>

                            <?php if (!empty($p['sku'])): ?>
                                · SKU: <?= esc($p['sku']) ?>
                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="ms-field">

                <label>
                    Qty *
                </label>

                <input
                    class="ms-input qty"
                    type="number"
                    min="1"
                    step="1"
                    value="1"
                    required
                >

            </div>


            <div class="ms-field">

                <label>
                    Unit cost *
                </label>

                <input
                    class="ms-input cost"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                >

            </div>


            <div class="ms-field">

                <label>
                    Tax %
                </label>

                <input
                    class="ms-input tax"
                    type="number"
                    min="0"
                    step="0.001"
                    value="0"
                >

            </div>


            <div
                class="ms-actions"
                style="
                    align-items:end;
                "
            >

                <button
                    class="ms-btn ms-btn-secondary is-sm scan-row"
                    type="button"
                >
                    📷 Scan
                </button>


                <button
                    class="ms-btn is-danger is-sm remove-row"
                    type="button"
                >
                    Remove
                </button>

            </div>

        </div>


        <div class="serial-container"></div>

    </div>

</template>


<?= $this->endSection() ?>


<?= $this->section('scripts') ?>


<!-- =============================================================
     CAMERA SCANNER LIBRARY
     Rocket Loader disabled for this script.
     ============================================================= -->

<script
    data-cfasync="false"
    src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"
></script>


<script data-cfasync="false">

'use strict';


/* ============================================================
   STATE
   ============================================================ */

let purchaseIndex = 0;

let activeRow = null;
let activeUnitRow = null;

let scanner = null;
let scannerRunning = false;

let availableCameras = [];

let currentCameraIndex = 0;
let currentCameraId = null;

let switchingCamera = false;
let scanProcessing = false;

let lastScannedCode = '';
let lastScannedAt = 0;


/* ============================================================
   BASIC HELPERS
   ============================================================ */

function normalizeCode(value)
{
    return String(value || '')
        .trim()
        .replace(/\r?\n/g, '')
        .trim();
}


function escapeHtml(value)
{
    const div =
        document.createElement('div');

    div.textContent =
        value == null
            ? ''
            : String(value);

    return div.innerHTML;
}


function showScanStatus(
    message,
    type = 'info'
)
{
    const box =
        document.getElementById(
            'scanStatus'
        );

    if (!box) {
        return;
    }

    box.style.display =
        'block';

    box.innerHTML =
        escapeHtml(message);

    if (type === 'error') {

        box.style.borderColor =
            '#d33';

    } else {

        box.style.borderColor =
            '';
    }
}


/* ============================================================
   GREEN SCAN SUCCESS
   ============================================================ */

function showScannerSuccess()
{
    const box =
        document.getElementById(
            'scannerPreviewBox'
        );

    if (!box) {
        return;
    }


    box.style.borderColor =
        '#20a464';

    box.style.backgroundColor =
        '#20a464';


    setTimeout(
        function() {

            box.style.borderColor =
                'transparent';

            box.style.backgroundColor =
                '#111';

        },
        700
    );
}


/* ============================================================
   DUPLICATE IDENTIFIER IN CURRENT PURCHASE
   ============================================================ */

function identifierAlreadyScanned(
    code,
    exceptInput = null
)
{
    const wanted =
        normalizeCode(
            code
        ).toLowerCase();


    if (!wanted) {
        return null;
    }


    const inputs =
        document.querySelectorAll(
            '.serial-container input[name*="[imei1]"],' +
            '.serial-container input[name*="[imei2]"],' +
            '.serial-container input[name*="[serial_no]"],' +
            '.serial-container input[name*="[unique_id]"]'
        );


    for (
        const input of inputs
    ) {

        if (
            input ===
            exceptInput
        ) {
            continue;
        }


        const current =
            normalizeCode(
                input.value
            ).toLowerCase();


        if (
            current &&
            current ===
                wanted
        ) {

            return input;
        }
    }


    return null;
}


/* ============================================================
   DATABASE DUPLICATE CHECK
   ============================================================ */

async function checkIdentifierInDatabase(
    code
)
{
    try {

        const csrfName =
            '<?= csrf_token() ?>';


        const csrfInput =
            document.querySelector(
                `input[name="${csrfName}"]`
            );


        const body =
            new URLSearchParams();


        body.append(
            csrfName,
            csrfInput?.value ||
            '<?= csrf_hash() ?>'
        );


        body.append(
            'identifier',
            code
        );


        const response =
            await fetch(
                '<?= site_url('purchases/check-identifier') ?>',
                {
                    method:
                        'POST',

                    headers: {

                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    body:
                        body.toString()
                }
            );


        const data =
            await response.json();


        if (
            data.csrfHash
        ) {

            document
                .querySelectorAll(
                    `input[name="${csrfName}"]`
                )
                .forEach(
                    function(input) {

                        input.value =
                            data.csrfHash;

                    }
                );
        }


        return data;


    } catch (error) {

        console.error(
            'Identifier database check failed:',
            error
        );


        return {

            ok: false,

            duplicate: false,

            error:
                'Unable to check identifier with server.'
        };
    }
}


/* ============================================================
   PRODUCT ROW
   ============================================================ */

window.addPurchaseRow = function()
{
    const template =
        document.getElementById(
            'purchaseRowTemplate'
        );


    const container =
        document.getElementById(
            'purchaseRows'
        );


    if (!template) {

        console.error(
            'purchaseRowTemplate not found'
        );

        return null;
    }


    if (!container) {

        console.error(
            'purchaseRows not found'
        );

        return null;
    }


    const node =
        template.content
            .firstElementChild
            .cloneNode(true);


    const i =
        purchaseIndex++;


    const product =
        node.querySelector(
            '.product-select'
        );


    const qty =
        node.querySelector(
            '.qty'
        );


    const cost =
        node.querySelector(
            '.cost'
        );


    const tax =
        node.querySelector(
            '.tax'
        );


    const removeButton =
        node.querySelector(
            '.remove-row'
        );


    const scanButton =
        node.querySelector(
            '.scan-row'
        );


    if (
        !product ||
        !qty ||
        !cost ||
        !tax
    ) {

        console.error(
            'Purchase template is missing required fields'
        );

        return null;
    }


    product.name =
        `items[${i}][product_id]`;


    qty.name =
        `items[${i}][qty]`;


    cost.name =
        `items[${i}][unit_cost]`;


    tax.name =
        `items[${i}][tax_percent]`;


    product.addEventListener(
        'change',
        function() {

            const option =
                product.selectedOptions[0];


            tax.value =
                option?.dataset.tax ||
                0;


            renderPurchaseUnits(
                node,
                i
            );
        }
    );


    qty.addEventListener(
        'input',
        function() {

            renderPurchaseUnits(
                node,
                i
            );
        }
    );


    if (
        removeButton
    ) {

        removeButton.addEventListener(
            'click',
            function(event) {

                event.preventDefault();
                event.stopPropagation();


                if (
                    activeRow ===
                    node
                ) {

                    activeRow =
                        null;

                    activeUnitRow =
                        null;
                }


                node.remove();
            }
        );
    }


    if (
        scanButton
    ) {

        scanButton.addEventListener(
            'click',
            function(event) {

                event.preventDefault();
                event.stopPropagation();


                activeRow =
                    node;

                activeUnitRow =
                    null;


                window.openScanner(
                    'Scan IMEI, serial number, or unique ID for this product.'
                );
            }
        );
    }


    container.appendChild(
        node
    );


    return node;
};


/* ============================================================
   SERIALIZED UNIT ROWS
   ============================================================ */

function renderPurchaseUnits(
    row,
    i
)
{
    const product =
        row.querySelector(
            '.product-select'
        );


    const option =
        product.selectedOptions[0];


    const box =
        row.querySelector(
            '.serial-container'
        );


    if (!box) {
        return;
    }


    const serialized =
        option?.dataset.serialized ===
        '1';


    if (!serialized) {

        box.innerHTML = '';

        return;
    }


    const qty =
        Math.max(
            1,
            parseInt(
                row.querySelector(
                    '.qty'
                ).value ||
                1,
                10
            )
        );


    row.querySelector(
        '.qty'
    ).step =
        '1';


    /*
     * Preserve all existing values.
     */
    const oldRows =
        Array.from(
            box.querySelectorAll(
                '.serial-unit-row'
            )
        );


    const oldUnits =
        oldRows.map(
            function(unitRow) {

                return {

                    imei1:
                        unitRow.querySelector(
                            '.unit-imei1'
                        )?.value ||
                        '',

                    imei2:
                        unitRow.querySelector(
                            '.unit-imei2'
                        )?.value ||
                        '',

                    serial_no:
                        unitRow.querySelector(
                            '.unit-serial'
                        )?.value ||
                        '',

                    unique_id:
                        unitRow.querySelector(
                            '.unit-unique'
                        )?.value ||
                        '',

                    color:
                        unitRow.querySelector(
                            '.unit-color'
                        )?.value ||
                        '',

                    storage_variant:
                        unitRow.querySelector(
                            '.unit-storage'
                        )?.value ||
                        ''
                };
            }
        );


    const wrap =
        document.createElement(
            'div'
        );


    wrap.className =
        'ms-internal';


    const title =
        document.createElement(
            'strong'
        );


    title.textContent =
        'Unit identities';


    wrap.appendChild(
        title
    );


    const help =
        document.createElement(
            'div'
        );


    help.className =
        'ms-help';


    help.textContent =
        'Scan each physical unit. Each scan fills one unit row.';


    wrap.appendChild(
        help
    );


    for (
        let u = 0;
        u < qty;
        u++
    ) {

        const old =
            oldUnits[u] ||
            {};


        const unitRow =
            document.createElement(
                'div'
            );


        unitRow.className =
            'serial-row serial-unit-row';


        unitRow.dataset.unitIndex =
            String(u);


        unitRow.innerHTML =

            '<input class="ms-input unit-imei1" ' +
                `name="items[${i}][units][${u}][imei1]" ` +
                'placeholder="IMEI 1">' +

            '<input class="ms-input unit-imei2" ' +
                `name="items[${i}][units][${u}][imei2]" ` +
                'placeholder="IMEI 2">' +

            '<input class="ms-input unit-serial" ' +
                `name="items[${i}][units][${u}][serial_no]" ` +
                'placeholder="Serial no.">' +

            '<input class="ms-input unit-unique" ' +
                `name="items[${i}][units][${u}][unique_id]" ` +
                'placeholder="Unique ID">' +

            '<input class="ms-input unit-color" ' +
                `name="items[${i}][units][${u}][color]" ` +
                'placeholder="Color">' +

            '<input class="ms-input unit-storage" ' +
                `name="items[${i}][units][${u}][storage_variant]" ` +
                'placeholder="Variant / storage">' +

            '<button ' +
                'class="ms-btn ms-btn-secondary is-sm scan-this-unit" ' +
                'type="button">' +
                '📷' +
            '</button>';


        /*
         * Restore old values.
         */

        unitRow.querySelector(
            '.unit-imei1'
        ).value =
            old.imei1 ||
            '';


        unitRow.querySelector(
            '.unit-imei2'
        ).value =
            old.imei2 ||
            '';


        unitRow.querySelector(
            '.unit-serial'
        ).value =
            old.serial_no ||
            '';


        unitRow.querySelector(
            '.unit-unique'
        ).value =
            old.unique_id ||
            '';


        unitRow.querySelector(
            '.unit-color'
        ).value =
            old.color ||
            '';


        unitRow.querySelector(
            '.unit-storage'
        ).value =
            old.storage_variant ||
            '';


        /*
         * Scan this specific unit.
         */
        const unitScan =
            unitRow.querySelector(
                '.scan-this-unit'
            );


        if (
            unitScan
        ) {

            unitScan.addEventListener(
                'click',
                function(event) {

                    event.preventDefault();
                    event.stopPropagation();


                    activeRow =
                        row;

                    activeUnitRow =
                        unitRow;


                    window.openScanner(
                        'Scan identifier for unit ' +
                        (u + 1) +
                        '.'
                    );
                }
            );
        }


        wrap.appendChild(
            unitRow
        );
    }


    box.replaceChildren(
        wrap
    );
}


/* ============================================================
   ROW INDEX
   ============================================================ */

function getRowIndex(
    row
)
{
    const select =
        row.querySelector(
            '.product-select'
        );


    const match =
        (select?.name || '')
            .match(
                /^items\[(\d+)\]/
            );


    return match
        ? parseInt(
            match[1],
            10
        )
        : 0;
}


/* ============================================================
   OPEN SCANNER
   ============================================================ */

window.openScanner = async function(
    hint
)
{
    const dialog =
        document.getElementById(
            'purchaseScannerDialog'
        );


    if (!dialog) {

        console.error(
            'purchaseScannerDialog not found'
        );

        return;
    }


    document.getElementById(
        'scannerHint'
    ).textContent =
        hint;


    document.getElementById(
        'scannerResult'
    ).textContent =
        'Detecting cameras...';


    if (
        !dialog.open
    ) {

        dialog.showModal();
    }


    setTimeout(
        function() {

            const input =
                document.getElementById(
                    'scannerManualInput'
                );


            if (input) {
                input.focus();
            }

        },
        250
    );


    await loadAvailableCameras();
};


/* ============================================================
   CAMERA NAME
   ============================================================ */

function getCameraDisplayName(
    camera,
    index
)
{
    const label =
        String(
            camera?.label ||
            ''
        ).trim();


    if (
        label
    ) {
        return label;
    }


    return 'Camera ' +
        (index + 1);
}


/* ============================================================
   CAMERA DETECTION
   ============================================================ */

async function loadAvailableCameras()
{
    const select =
        document.getElementById(
            'cameraSelect'
        );


    try {

        if (
            typeof Html5Qrcode ===
            'undefined'
        ) {

            select.innerHTML =
                '<option value="">Scanner library unavailable</option>';


            document.getElementById(
                'scannerResult'
            ).textContent =
                'Scanner library could not be loaded. Use USB/Bluetooth scanner.';


            return;
        }


        const cameras =
            await Html5Qrcode.getCameras();


        availableCameras =
            Array.isArray(
                cameras
            )
                ? cameras
                : [];


        if (
            availableCameras.length ===
            0
        ) {

            select.innerHTML =
                '<option value="">No camera detected</option>';


            document.getElementById(
                'scannerResult'
            ).textContent =
                'No camera detected. Use USB/Bluetooth scanner.';


            return;
        }


        /*
         * Build camera list.
         */
        select.innerHTML =
            '';


        availableCameras.forEach(
            function(
                camera,
                index
            ) {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    camera.id;


                option.textContent =
                    getCameraDisplayName(
                        camera,
                        index
                    );


                select.appendChild(
                    option
                );
            }
        );


        /*
         * Preserve previously selected camera.
         * Otherwise use rear/main camera heuristic.
         */
        if (
            currentCameraId
        ) {

            const existingIndex =
                availableCameras.findIndex(
                    function(camera) {

                        return camera.id ===
                            currentCameraId;
                    }
                );


            if (
                existingIndex >= 0
            ) {

                currentCameraIndex =
                    existingIndex;

            } else {

                currentCameraIndex =
                    chooseBestCameraIndex(
                        availableCameras
                    );
            }

        } else {

            currentCameraIndex =
                chooseBestCameraIndex(
                    availableCameras
                );
        }


        currentCameraId =
            availableCameras[
                currentCameraIndex
            ].id;


        select.value =
            currentCameraId;


        await startSelectedCamera();


    } catch (error) {

        console.error(
            'Camera detection failed:',
            error
        );


        availableCameras =
            [];


        select.innerHTML =
            '<option value="">Camera unavailable</option>';


        document.getElementById(
            'scannerResult'
        ).textContent =
            'Camera unavailable. Allow camera permission or use USB/Bluetooth scanner.';
    }
}


/* ============================================================
   CHOOSE BEST INITIAL CAMERA
   ============================================================ */

function chooseBestCameraIndex(
    cameras
)
{
    if (
        !cameras.length
    ) {
        return 0;
    }


    function score(
        camera
    )
    {
        const label =
            String(
                camera?.label ||
                ''
            ).toLowerCase();


        let value =
            0;


        if (
            label.includes('back') ||
            label.includes('rear')
        ) {

            value +=
                100;
        }


        if (
            label.includes('main') ||
            label.includes('primary')
        ) {

            value +=
                80;
        }


        if (
            label.includes('wide')
        ) {

            value +=
                20;
        }


        if (
            label.includes('front') ||
            label.includes('selfie') ||
            label.includes('user')
        ) {

            value -=
                100;
        }


        return value;
    }


    let bestIndex =
        0;

    let bestScore =
        -Infinity;


    cameras.forEach(
        function(
            camera,
            index
        ) {

            const cameraScore =
                score(camera);


            if (
                cameraScore >
                bestScore
            ) {

                bestScore =
                    cameraScore;

                bestIndex =
                    index;
            }
        }
    );


    return bestIndex;
}


/* ============================================================
   START SELECTED CAMERA
   ============================================================ */

async function startSelectedCamera()
{
    if (
        !availableCameras.length
    ) {
        return;
    }


    if (
        switchingCamera
    ) {
        return;
    }


    const camera =
        availableCameras[
            currentCameraIndex
        ];


    if (!camera) {
        return;
    }


    const cameraId =
        camera.id;


    const cameraName =
        getCameraDisplayName(
            camera,
            currentCameraIndex
        );


    switchingCamera =
        true;


    try {

        /*
         * Create scanner only once.
         */
        if (
            !scanner
        ) {

            scanner =
                new Html5Qrcode(
                    'purchaseQrReader'
                );
        }


        /*
         * Stop current stream only.
         *
         * Do not clear() here.
         * Do not destroy scanner.
         * Do not close dialog.
         */
        if (
            scannerRunning
        ) {

            try {

                await scanner.stop();

            } catch (error) {

                console.warn(
                    'Previous camera stop:',
                    error
                );
            }


            scannerRunning =
                false;
        }


        /*
         * Start requested camera by device ID.
         *
         * We deliberately do NOT specify
         * formatsToSupport because different
         * html5-qrcode builds can expose different
         * format constants.
         */
        await scanner.start(

            cameraId,

            {
                fps:
                    10,

                qrbox: {
                    width:
                        270,

                    height:
                        150
                },

                aspectRatio:
                    16 / 9
            },

            function(decodedText) {

                window.handleScannedCode(
                    decodedText
                );

            },

            function() {

                /*
                 * Normal camera frame
                 * with no detection.
                 */

            }
        );


        scannerRunning =
            true;


        currentCameraId =
            cameraId;


        const select =
            document.getElementById(
                'cameraSelect'
            );


        if (
            select
        ) {

            select.value =
                cameraId;
        }


        document.getElementById(
            'scannerResult'
        ).textContent =
            'Camera: ' +
            cameraName;


    } catch (error) {

        console.error(
            'Camera start failed:',
            error
        );


        scannerRunning =
            false;


        document.getElementById(
            'scannerResult'
        ).textContent =
            'Unable to start "' +
            cameraName +
            '". Try another camera.';

    } finally {

        switchingCamera =
            false;
    }
}


/* ============================================================
   SWITCH CAMERA
   ============================================================ */

async function switchCamera(
    direction
)
{
    if (
        availableCameras.length <
        2
    ) {

        document.getElementById(
            'scannerResult'
        ).textContent =
            'Only one camera is available.';

        return;
    }


    if (
        switchingCamera
    ) {
        return;
    }


    let nextIndex =
        currentCameraIndex +
        direction;


    if (
        nextIndex >=
        availableCameras.length
    ) {

        nextIndex =
            0;
    }


    if (
        nextIndex <
        0
    ) {

        nextIndex =
            availableCameras.length -
            1;
    }


    currentCameraIndex =
        nextIndex;


    currentCameraId =
        availableCameras[
            currentCameraIndex
        ].id;


    await startSelectedCamera();
}


/* ============================================================
   CAMERA BUTTONS
   ============================================================ */

const previousCameraButton =
    document.getElementById(
        'previousCameraButton'
    );


const nextCameraButton =
    document.getElementById(
        'nextCameraButton'
    );


const switchCameraButton =
    document.getElementById(
        'switchCameraButton'
    );


if (
    previousCameraButton
) {

    previousCameraButton.addEventListener(
        'click',
        async function(event) {

            event.preventDefault();
            event.stopPropagation();

            await switchCamera(-1);
        }
    );
}


if (
    nextCameraButton
) {

    nextCameraButton.addEventListener(
        'click',
        async function(event) {

            event.preventDefault();
            event.stopPropagation();

            await switchCamera(1);
        }
    );
}


if (
    switchCameraButton
) {

    switchCameraButton.addEventListener(
        'click',
        async function(event) {

            event.preventDefault();
            event.stopPropagation();

            await switchCamera(1);
        }
    );
}


/* ============================================================
   CAMERA DROPDOWN
   ============================================================ */

const cameraSelect =
    document.getElementById(
        'cameraSelect'
    );


if (
    cameraSelect
) {

    cameraSelect.addEventListener(
        'change',
        async function(event) {

            event.preventDefault();
            event.stopPropagation();


            if (
                switchingCamera
            ) {
                return;
            }


            const cameraId =
                this.value;


            const index =
                availableCameras.findIndex(
                    function(camera) {

                        return camera.id ===
                            cameraId;
                    }
                );


            if (
                index <
                0
            ) {
                return;
            }


            currentCameraIndex =
                index;


            currentCameraId =
                cameraId;


            await startSelectedCamera();
        }
    );
}


/* ============================================================
   STOP CAMERA
   ============================================================ */

async function stopCameraScanner()
{
    if (
        !scanner ||
        !scannerRunning
    ) {
        return;
    }


    try {

        await scanner.stop();

    } catch (error) {

        console.warn(
            'Camera stop:',
            error
        );
    }


    scannerRunning =
        false;
}


/* ============================================================
   CLOSE SCANNER
   ============================================================ */

window.closeScanner =
    async function()
    {
        await stopCameraScanner();


        const dialog =
            document.getElementById(
                'purchaseScannerDialog'
            );


        if (
            dialog &&
            dialog.open
        ) {

            dialog.close();
        }


        const input =
            document.getElementById(
                'scannerManualInput'
            );


        if (
            input
        ) {

            input.value =
                '';
        }


        activeRow =
            null;

        activeUnitRow =
            null;
    };


const closeScannerButton =
    document.getElementById(
        'closeScannerButton'
    );


if (
    closeScannerButton
) {

    closeScannerButton.addEventListener(
        'click',
        function(event) {

            event.preventDefault();
            event.stopPropagation();

            window.closeScanner();
        }
    );
}


/* ============================================================
   USB / BLUETOOTH SCANNER
   ============================================================ */

const scannerManualInput =
    document.getElementById(
        'scannerManualInput'
    );


if (
    scannerManualInput
) {

    scannerManualInput.addEventListener(
        'keydown',
        function(event) {

            if (
                event.key !==
                'Enter'
            ) {
                return;
            }


            event.preventDefault();


            const code =
                normalizeCode(
                    this.value
                );


            this.value =
                '';


            if (
                code
            ) {

                window.handleScannedCode(
                    code
                );
            }
        }
    );
}


/* ============================================================
   MAIN SCAN HANDLER
   ============================================================ */

window.handleScannedCode =
    async function(
        rawCode
    )
    {
        if (
            scanProcessing
        ) {
            return;
        }


        const code =
            normalizeCode(
                rawCode
            );


        if (
            !code
        ) {
            return;
        }


        const now =
            Date.now();


        /*
         * Prevent same camera result
         * from firing repeatedly.
         */
        if (
            code ===
                lastScannedCode &&
            now -
                lastScannedAt <
                1500
        ) {

            return;
        }


        lastScannedCode =
            code;

        lastScannedAt =
            now;


        scanProcessing =
            true;


        try {

            const resultBox =
                document.getElementById(
                    'scannerResult'
                );


            if (
                resultBox
            ) {

                resultBox.textContent =
                    'Checking ' +
                    code +
                    '...';
            }


            /*
             * Duplicate within current purchase.
             */
            if (
                identifierAlreadyScanned(
                    code
                )
            ) {

                const message =
                    '❌ ' +
                    code +
                    ' has already been scanned in this purchase.';


                if (
                    resultBox
                ) {

                    resultBox.textContent =
                        message;
                }


                showScanStatus(
                    message,
                    'error'
                );


                return;
            }


            const looksLikeImei =
                /^\d{15}$/.test(
                    code
                );


            /*
             * If a tracked product is active,
             * or code looks like an IMEI,
             * check database.
             */
            if (
                activeRow ||
                looksLikeImei
            ) {

                const databaseResult =
                    await checkIdentifierInDatabase(
                        code
                    );


                if (
                    !databaseResult.ok
                ) {

                    const message =
                        databaseResult.error ||
                        'Unable to check identifier.';


                    if (
                        resultBox
                    ) {

                        resultBox.textContent =
                            '❌ ' +
                            message;
                    }


                    showScanStatus(
                        message,
                        'error'
                    );


                    return;
                }


                if (
                    databaseResult.duplicate
                ) {

                    const message =
                        databaseResult.message ||
                        'This identifier already exists in inventory.';


                    if (
                        resultBox
                    ) {

                        resultBox.textContent =
                            '❌ ' +
                            message;
                    }


                    showScanStatus(
                        message,
                        'error'
                    );


                    return;
                }
            }


            /*
             * Product row selected.
             */
            if (
                activeRow
            ) {

                const select =
                    activeRow.querySelector(
                        '.product-select'
                    );


                const option =
                    select?.selectedOptions[0];


                const isSerialized =
                    option?.dataset.serialized ===
                    '1';


                /*
                 * Tracked product.
                 */
                if (
                    isSerialized
                ) {

                    const success =
                        await fillTrackedUnit(
                            activeRow,
                            code
                        );


                    if (
                        success
                    ) {

                        showScannerSuccess();


                        if (
                            resultBox
                        ) {

                            resultBox.textContent =
                                '✓ ' +
                                code +
                                ' added. Scan next unit.';
                        }


                        showScanStatus(
                            '✓ ' +
                            code +
                            ' added. Scan the next unit.'
                        );
                    }


                    return;
                }


                /*
                 * Non-serialized product.
                 */
                addQuantityToAccessory(
                    activeRow
                );


                showScannerSuccess();


                if (
                    resultBox
                ) {

                    resultBox.textContent =
                        '✓ Quantity +1';
                }


                showScanStatus(
                    '✓ Quantity increased by 1.'
                );


                return;
            }


            /*
             * 15-digit IMEI without a product row.
             */
            if (
                looksLikeImei
            ) {

                const message =
                    'Select a tracked product first, then scan its IMEI.';


                if (
                    resultBox
                ) {

                    resultBox.textContent =
                        '❌ ' +
                        message;
                }


                showScanStatus(
                    message,
                    'error'
                );


                return;
            }


            /*
             * No selected product:
             * lookup barcode/SKU.
             */
            const productResult =
                await lookupProductByScan(
                    code
                );


            if (
                !productResult.ok
            ) {

                const message =
                    productResult.error ||
                    'No product found for this barcode/SKU.';


                if (
                    resultBox
                ) {

                    resultBox.textContent =
                        '❌ ' +
                        message;
                }


                showScanStatus(
                    message,
                    'error'
                );


                return;
            }


            const scannedProduct =
                productResult.product;


            /*
             * Add scanned product.
             */
            addScannedProduct(
                scannedProduct
            );


            showScannerSuccess();


            /*
             * For tracked products, addScannedProduct()
             * changes activeRow and waits for the IMEI.
             */
            if (
                Number(
                    scannedProduct.is_serialized
                ) === 1
            ) {

                if (
                    resultBox
                ) {

                    resultBox.textContent =
                        '✓ ' +
                        scannedProduct.name +
                        '. Scan its IMEI.';
                }

                return;
            }


            if (
                resultBox
            ) {

                resultBox.textContent =
                    '✓ Added ' +
                    scannedProduct.name;
            }


            showScanStatus(
                '✓ Added ' +
                scannedProduct.name
            );

        } finally {

            scanProcessing =
                false;
        }
    };


/* ============================================================
   FILL TRACKED UNIT
   ============================================================ */

async function fillTrackedUnit(
    row,
    code
)
{
    let unitRows =
        row.querySelectorAll(
            '.serial-unit-row'
        );


    /*
     * Create first row if needed.
     */
    if (
        !unitRows.length
    ) {

        row.querySelector(
            '.qty'
        ).value =
            '1';


        renderPurchaseUnits(
            row,
            getRowIndex(row)
        );


        unitRows =
            row.querySelectorAll(
                '.serial-unit-row'
            );
    }


    let target =
        activeUnitRow;


    /*
     * If no specific unit selected,
     * find first completely empty unit.
     */
    if (
        !target ||
        !row.contains(
            target
        )
    ) {

        target =
            null;


        for (
            const unitRow of unitRows
        ) {

            const values = [

                unitRow.querySelector(
                    '.unit-imei1'
                )?.value,

                unitRow.querySelector(
                    '.unit-imei2'
                )?.value,

                unitRow.querySelector(
                    '.unit-serial'
                )?.value,

                unitRow.querySelector(
                    '.unit-unique'
                )?.value

            ];


            const hasIdentifier =
                values.some(
                    function(value) {

                        return !!normalizeCode(
                            value
                        );
                    }
                );


            if (
                !hasIdentifier
            ) {

                target =
                    unitRow;

                break;
            }
        }
    }


    /*
     * No empty row:
     * increase quantity and create one.
     */
    if (
        !target
    ) {

        const qtyInput =
            row.querySelector(
                '.qty'
            );


        const currentQty =
            Math.max(
                1,
                parseInt(
                    qtyInput.value ||
                    '1',
                    10
                )
            );


        qtyInput.value =
            String(
                currentQty +
                1
            );


        renderPurchaseUnits(
            row,
            getRowIndex(row)
        );


        unitRows =
            row.querySelectorAll(
                '.serial-unit-row'
            );


        target =
            unitRows[
                unitRows.length -
                1
            ];
    }


    let field =
        null;


    /*
     * 15-digit number = IMEI.
     */
    if (
        /^\d{15}$/.test(
            code
        )
    ) {

        const imei1 =
            target.querySelector(
                '.unit-imei1'
            );


        const imei2 =
            target.querySelector(
                '.unit-imei2'
            );


        if (
            !normalizeCode(
                imei1.value
            )
        ) {

            field =
                imei1;

        } else if (
            !normalizeCode(
                imei2.value
            )
        ) {

            field =
                imei2;

        } else {

            /*
             * Both IMEIs already filled:
             * new unit.
             */
            const qtyInput =
                row.querySelector(
                    '.qty'
                );


            const currentQty =
                parseInt(
                    qtyInput.value ||
                    '1',
                    10
                );


            qtyInput.value =
                String(
                    currentQty +
                    1
                );


            renderPurchaseUnits(
                row,
                getRowIndex(row)
            );


            unitRows =
                row.querySelectorAll(
                    '.serial-unit-row'
                );


            target =
                unitRows[
                    unitRows.length -
                    1
                ];


            field =
                target.querySelector(
                    '.unit-imei1'
                );
        }

    } else {

        /*
         * Non-IMEI value:
         * Unique ID first, then Serial No.
         */
        const unique =
            target.querySelector(
                '.unit-unique'
            );


        const serial =
            target.querySelector(
                '.unit-serial'
            );


        if (
            !normalizeCode(
                unique.value
            )
        ) {

            field =
                unique;

        } else if (
            !normalizeCode(
                serial.value
            )
        ) {

            field =
                serial;

        } else {

            /*
             * Current unit is full.
             * Create another unit.
             */
            const qtyInput =
                row.querySelector(
                    '.qty'
                );


            const currentQty =
                parseInt(
                    qtyInput.value ||
                    '1',
                    10
                );


            qtyInput.value =
                String(
                    currentQty +
                    1
                );


            renderPurchaseUnits(
                row,
                getRowIndex(row)
            );


            unitRows =
                row.querySelectorAll(
                    '.serial-unit-row'
                );


            target =
                unitRows[
                    unitRows.length -
                    1
                ];


            field =
                target.querySelector(
                    '.unit-unique'
                );
        }
    }


    if (
        !field
    ) {

        return false;
    }


    /*
     * Browser-side duplicate protection.
     */
    if (
        identifierAlreadyScanned(
            code,
            field
        )
    ) {

        showScanStatus(
            '❌ Duplicate identifier: ' +
            code,
            'error'
        );


        return false;
    }


    field.value =
        code;


    field.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true
            }
        )
    );


    activeUnitRow =
        null;


    return true;
}


/* ============================================================
   ACCESSORY QUANTITY
   ============================================================ */

function addQuantityToAccessory(
    row
)
{
    const qty =
        row.querySelector(
            '.qty'
        );


    const current =
        Math.max(
            0,
            parseInt(
                qty.value ||
                '0',
                10
            )
        );


    qty.value =
        String(
            current +
            1
        );
}


/* ============================================================
   PRODUCT LOOKUP BY SKU
   ============================================================ */

async function lookupProductByScan(
    code
)
{
    try {

        const csrfName =
            '<?= csrf_token() ?>';


        const csrfInput =
            document.querySelector(
                `input[name="${csrfName}"]`
            );


        const body =
            new URLSearchParams();


        body.append(
            csrfName,
            csrfInput?.value ||
            '<?= csrf_hash() ?>'
        );


        body.append(
            'code',
            code
        );


        const response =
            await fetch(
                '<?= site_url('purchases/scan') ?>',
                {
                    method:
                        'POST',

                    headers: {

                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',

                        'X-Requested-With':
                            'XMLHttpRequest'
                    },

                    body:
                        body.toString()
                }
            );


        const data =
            await response.json();


        if (
            data.csrfHash
        ) {

            document
                .querySelectorAll(
                    `input[name="${csrfName}"]`
                )
                .forEach(
                    function(input) {

                        input.value =
                            data.csrfHash;

                    }
                );
        }


        return data;


    } catch (error) {

        console.error(
            'Product lookup failed:',
            error
        );


        return {

            ok: false,

            error:
                'Unable to check scanned product.'
        };
    }
}


/* ============================================================
   ADD SCANNED PRODUCT
   ============================================================ */

function addScannedProduct(
    productData
)
{
    let row =
        null;


    /*
     * Reuse existing same-product row.
     */
    document
        .querySelectorAll(
            '.purchase-row'
        )
        .forEach(
            function(candidate) {

                if (
                    row
                ) {
                    return;
                }


                const select =
                    candidate.querySelector(
                        '.product-select'
                    );


                if (
                    String(
                        select.value
                    ) ===
                    String(
                        productData.id
                    )
                ) {

                    row =
                        candidate;
                }
            }
        );


    /*
     * Create new row.
     */
    if (
        !row
    ) {

        row =
            window.addPurchaseRow();


        if (
            !row
        ) {
            return;
        }


        const select =
            row.querySelector(
                '.product-select'
            );


        select.value =
            String(
                productData.id
            );


        const option =
            select.selectedOptions[0];


        row.querySelector(
            '.tax'
        ).value =
            productData.tax_percent ??
            option?.dataset.tax ??
            0;
    }


    const isSerialized =
        row.querySelector(
            '.product-select'
        )
        .selectedOptions[0]
        ?.dataset.serialized ===
        '1';


    if (
        isSerialized
    ) {

        /*
         * Ensure at least one unit row.
         */
        const qty =
            row.querySelector(
                '.qty'
            );


        if (
            parseInt(
                qty.value ||
                '0',
                10
            ) <
            1
        ) {

            qty.value =
                '1';
        }


        renderPurchaseUnits(
            row,
            getRowIndex(row)
        );


        activeRow =
            row;


        activeUnitRow =
            null;


        return;
    }


    /*
     * Accessory:
     * each scan increments quantity.
     */
    const qty =
        row.querySelector(
            '.qty'
        );


    const current =
        parseInt(
            qty.value ||
            '0',
            10
        );


    if (
        row.dataset.scannedOnce ===
        '1'
    ) {

        qty.value =
            String(
                current +
                1
            );

    } else {

        qty.value =
            '1';


        row.dataset.scannedOnce =
            '1';
    }
}


/* ============================================================
   GLOBAL SCAN BUTTON
   ============================================================ */

const globalScanButton =
    document.getElementById(
        'globalScanButton'
    );


if (
    globalScanButton
) {

    globalScanButton.addEventListener(
        'click',
        function(event) {

            event.preventDefault();
            event.stopPropagation();


            activeRow =
                null;

            activeUnitRow =
                null;


            window.openScanner(
                'Scan accessory barcode/SKU, or select a tracked product first.'
            );
        }
    );
}


/* ============================================================
   SUPPLIER QUICK ADD
   ============================================================ */

const quickSupplier =
    document.getElementById(
        'quickSupplier'
    );


if (
    quickSupplier
) {

    quickSupplier.addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();


            const form =
                new FormData(
                    event.target
                );


            const csrfName =
                '<?= csrf_token() ?>';


            form.append(
                csrfName,
                document.querySelector(
                    `input[name="${csrfName}"]`
                )?.value ||
                '<?= csrf_hash() ?>'
            );


            try {

                const response =
                    await fetch(
                        '<?= site_url('suppliers/quick') ?>',
                        {
                            method:
                                'POST',

                            body:
                                form
                        }
                    );


                const data =
                    await response.json();


                if (
                    data.csrfHash
                ) {

                    document
                        .querySelectorAll(
                            `input[name="${csrfName}"]`
                        )
                        .forEach(
                            function(input) {

                                input.value =
                                    data.csrfHash;

                            }
                        );
                }


                if (
                    !data.ok
                ) {

                    shopToast(
                        data.error ||
                        'Unable to add supplier',
                        'error'
                    );


                    return;
                }


                const option =
                    new Option(
                        data.supplier.name,
                        data.supplier.id,
                        true,
                        true
                    );


                document
                    .getElementById(
                        'supplierSelect'
                    )
                    .add(option);


                document
                    .getElementById(
                        'supplierQuickDialog'
                    )
                    .close();


                event.target.reset();


                shopToast(
                    'Supplier added'
                );


            } catch (error) {

                console.error(
                    'Supplier error:',
                    error
                );


                shopToast(
                    'Unable to add supplier',
                    'error'
                );
            }
        }
    );
}


/* ============================================================
   INITIAL ROW
   ============================================================ */

window.addPurchaseRow();


/* ============================================================
   DEBUG / READY
   ============================================================ */

window.purchaseScannerReady =
    true;


console.log(
    'Purchase scanner initialized successfully.'
);

</script>


<?= $this->endSection() ?>