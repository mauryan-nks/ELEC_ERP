<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="ms-page-head">
    <div>
        <h1>Users & Roles</h1>
        <p>Internal staff logins for this shop. Email is managed here; passwords are securely hashed and can only be replaced, never viewed.</p>
    </div>
    <button class="ms-btn ms-btn-primary" type="button" data-open-dialog="userDialog">+ Add User</button>
</div>

<div class="ms-table-tools">
    <div class="ms-search"><input class="ms-input" data-table-filter="#usersTable" placeholder="Search staff, email, phone or role"></div>
    <div class="ms-chip-row"><span class="ms-chip"><?= count($rows) ?> staff logins</span></div>
</div>

<div class="ms-table-scroll">
    <table class="ms-table" id="usersTable">
        <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): $role = $u['groups'][0] ?? 'sales'; ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><strong><?= esc($u['full_name'] ?? '-') ?></strong></td>
                <td><?= esc($u['username'] ?? '-') ?></td>
                <td><?= esc($u['email'] ?? '-') ?></td>
                <td><?= esc($u['phone'] ?? '-') ?></td>
                <td><?php foreach ($u['groups'] as $g): ?><span class="ms-badge is-neutral"><?= esc(ucwords(str_replace('_', ' ', $g))) ?></span> <?php endforeach; ?></td>
                <td><span class="ms-badge <?= $u['active'] ? 'is-ok' : 'is-danger' ?>"><?= $u['active'] ? 'Active' : 'Disabled' ?></span></td>
                <td>
                    <div class="ms-actions ms-nowrap">
                        <button class="ms-btn ms-btn-secondary is-sm" type="button" data-open-dialog="editUser<?= (int) $u['id'] ?>">Edit</button>
                        <?php if ((int) $u['id'] !== (int) auth()->id()): ?>
                        <form method="post" action="<?= site_url('users/' . $u['id'] . '/status') ?>" class="ms-inline" data-confirm="<?= $u['active'] ? 'Disable this staff login?' : 'Enable this staff login?' ?>">
                            <?= csrf_field() ?><input type="hidden" name="active" value="<?= $u['active'] ? 0 : 1 ?>"><button class="ms-btn ms-btn-ghost is-sm" type="submit"><?= $u['active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (! $rows): ?><tr class="ms-no-filter"><td colspan="8" class="ms-empty">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php foreach ($rows as $u): $role = $u['groups'][0] ?? 'sales'; ?>
<dialog id="editUser<?= (int) $u['id'] ?>">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head"><div><h3>Edit staff login</h3><span class="ms-muted"><?= esc($u['email'] ?? '') ?></span></div><button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button></div>
        <form method="post" action="<?= site_url('users/' . $u['id']) ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field"><label>Full Name</label><input class="ms-input" name="full_name" value="<?= esc($u['full_name'] ?? '') ?>"></div>
                <div class="ms-field"><label>Phone</label><input class="ms-input" name="phone" value="<?= esc($u['phone'] ?? '') ?>" inputmode="tel"></div>
                <div class="ms-field"><label>Username</label><input class="ms-input" name="username" value="<?= esc($u['username'] ?? '') ?>" autocomplete="username"></div>
                <div class="ms-field"><label>Email *</label><input class="ms-input" type="email" name="email" required value="<?= esc($u['email'] ?? '') ?>" autocomplete="email"></div>
                <div class="ms-field"><label>New Password</label><input class="ms-input" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password"><div class="ms-help">Existing passwords are never displayed.</div></div>
                <div class="ms-field"><label>Role *</label><select class="ms-select" name="group" required><?php foreach ($groups as $g): ?><option value="<?= esc($g) ?>" <?= $role === $g ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $g))) ?></option><?php endforeach; ?></select></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Save User</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endforeach; ?>

<dialog id="userDialog">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head"><div><h3>Create Staff Login</h3><span class="ms-muted">Create an internal account and assign a role.</span></div><button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button></div>
        <form method="post" action="<?= site_url('users') ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field"><label>Full Name</label><input class="ms-input" name="full_name"></div>
                <div class="ms-field"><label>Phone</label><input class="ms-input" name="phone" inputmode="tel"></div>
                <div class="ms-field"><label>Username</label><input class="ms-input" name="username" autocomplete="username"></div>
                <div class="ms-field"><label>Email *</label><input class="ms-input" type="email" name="email" required autocomplete="email"></div>
                <div class="ms-field"><label>Password *</label><input class="ms-input" type="password" name="password" minlength="8" autocomplete="new-password" required></div>
                <div class="ms-field"><label>Role *</label><select class="ms-select" name="group" required><?php foreach ($groups as $g): ?><option value="<?= esc($g) ?>"><?= esc(ucwords(str_replace('_', ' ', $g))) ?></option><?php endforeach; ?></select></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Create Login</button></div>
            </div>
        </form>
    </div>
</dialog>

<?= $this->endSection() ?>
