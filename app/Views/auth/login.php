<?= $this->extend(config('Auth')->views['layout']) ?>
<?= $this->section('title') ?>Sign in<?= $this->endSection() ?>
<?= $this->section('main') ?>
<div class="ms-auth-head">
    <div class="ms-auth-mobile-mark">MS</div>
    <p class="ms-auth-eyebrow">MobileShop ERP</p>
    <h2>Welcome back</h2>
    <p>Sign in with your staff email and password.</p>
</div>
<?php if (session('error') !== null): ?><div class="ms-alert is-error" role="alert"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('message') !== null): ?><div class="ms-alert is-success" role="status"><?= esc(session('message')) ?></div><?php endif; ?>
<?php if (session('errors') !== null): ?><div class="ms-alert is-error" role="alert"><ul class="ms-error-list"><?php foreach ((array) session('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form action="<?= url_to('login') ?>" method="post" class="ms-auth-form">
    <?= csrf_field() ?>
    <div class="ms-field">
        <label for="loginEmail">Email</label>
        <input class="ms-input" id="loginEmail" type="email" name="email" value="<?= old('email') ?>" autocomplete="username" inputmode="email" required autofocus>
    </div>
    <div class="ms-field">
        <label for="loginPassword">Password</label>
        <div class="ms-password-wrap"><input class="ms-input" id="loginPassword" type="password" name="password" autocomplete="current-password" required><button class="ms-password-toggle" type="button" data-password-toggle="loginPassword" aria-label="Show password">Show</button></div>
    </div>
    <?php if (setting('Auth.sessionConfig')['allowRemembering'] ?? false): ?>
    <label class="ms-check"><input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>><span>Keep me signed in</span></label>
    <?php endif; ?>
    <button class="ms-btn ms-btn-primary ms-auth-submit" type="submit">Sign in</button>
</form>
<p class="ms-auth-note">Accounts are created by the shop owner or administrator.</p>
<script>document.addEventListener('click',function(e){var b=e.target.closest('[data-password-toggle]');if(!b)return;var i=document.getElementById(b.dataset.passwordToggle);if(!i)return;var show=i.type==='password';i.type=show?'text':'password';b.textContent=show?'Hide':'Show';b.setAttribute('aria-label',show?'Hide password':'Show password')});</script>
<?= $this->endSection() ?>
