<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="ms-page-head">
    <div>
        <h1>Borrowed stock</h1>
        <p>Internal record of items sourced from another shop. Source and cost stay private and never appear on the customer invoice.</p>
    </div>
    <div class="ms-actions">
        <a class="ms-btn ms-btn-secondary" href="<?= site_url('suppliers') ?>">Manage source stores</a>
        <a class="ms-btn ms-btn-primary" href="<?= site_url('sales/new') ?>">Sell borrowed item</a>
    </div>
</div>

<div class="ms-table-tools">
    <div class="ms-search"><input class="ms-input" data-table-filter="#borrowedTable" placeholder="Search reference, source or status"></div>
    <div class="ms-chip-row"><span class="ms-chip"><?= count($rows) ?> records</span></div>
</div>

<div class="ms-table-scroll">
    <table class="ms-table" id="borrowedTable">
        <thead><tr><th>Reference</th><th>Source store/person</th><th>Borrowed at</th><th>Received</th><th>Available</th><th>Cost value</th><th>Settlement</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= esc($r['reference_no'] ?? '-') ?></td>
                <td><strong><?= esc($r['source_name'] ?? 'Source noted on sale') ?></strong><br><span class="ms-muted"><?= esc($r['notes'] ?? '') ?></span></td>
                <td><?= esc($r['borrowed_at']) ?></td>
                <td><?= number_format((float) $r['qty_received'], 0) ?></td>
                <td><?= number_format((float) $r['qty_available'], 0) ?></td>
                <td>₹<?= number_format((float) $r['cost_value'], 2) ?></td>
                <td><span class="ms-badge <?= $r['settlement_status'] === 'settled' ? 'is-ok' : ($r['settlement_status'] === 'returned' ? 'is-neutral' : 'is-warn') ?>"><?= esc(ucwords(str_replace('_', ' ', $r['settlement_status']))) ?></span></td>
                <td><button class="ms-btn ms-btn-secondary is-sm" type="button" data-open-dialog="borrow<?= (int) $r['id'] ?>">Update</button></td>
            </tr>
        <?php endforeach; ?>
        <?php if (! $rows): ?><tr class="ms-no-filter"><td colspan="8" class="ms-empty">No borrowed stock has been recorded.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php foreach ($rows as $r): ?>
<dialog id="borrow<?= (int) $r['id'] ?>">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head">
            <div><h3>Update borrowed stock</h3><span class="ms-muted"><?= esc($r['reference_no'] ?? '') ?></span></div>
            <button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button>
        </div>
        <form method="post" action="<?= site_url('borrowed-stock/' . $r['id']) ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field ms-full">
                    <label>Settlement status</label>
                    <select class="ms-select" name="settlement_status">
                        <?php foreach (['open','partly_settled','settled','returned'] as $s): ?>
                            <option value="<?= $s ?>" <?= $r['settlement_status'] === $s ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $s))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-field ms-full"><label>Internal notes</label><textarea class="ms-textarea" name="notes" rows="4"><?= esc($r['notes'] ?? '') ?></textarea></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Save Status</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endforeach; ?>

<?= $this->endSection() ?>
