(function () {
    'use strict';

    const root = document.documentElement;
    const body = document.body;
    const sidebar = document.getElementById('shopSidebar');
    const scrim = document.getElementById('shopScrim');
    const menuButton = document.getElementById('shopMenuButton');
    const themeMeta = document.getElementById('themeColorMeta');

    function preferredTheme() {
        const saved = localStorage.getItem('shop-theme');
        if (saved === 'light' || saved === 'dark') return saved;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        root.dataset.theme = theme;
        document.querySelectorAll('[data-theme-icon]').forEach((el) => { el.textContent = theme === 'dark' ? '☀' : '☾'; });
        document.querySelectorAll('[data-theme-label]').forEach((el) => { el.textContent = theme === 'dark' ? 'Light' : 'Dark'; });
        if (themeMeta) themeMeta.setAttribute('content', theme === 'dark' ? '#151a22' : '#edf2f8');
    }

    window.shopSetTheme = function (theme) {
        localStorage.setItem('shop-theme', theme);
        applyTheme(theme);
    };

    window.shopToggleTheme = function () {
        window.shopSetTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
    };

    applyTheme(preferredTheme());

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-theme-toggle]')) window.shopToggleTheme();
    });

    if (window.matchMedia) {
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const sync = () => { if (!localStorage.getItem('shop-theme')) applyTheme(preferredTheme()); };
        if (typeof media.addEventListener === 'function') media.addEventListener('change', sync);
    }

    function setSidebar(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', open);
        scrim?.classList.toggle('is-open', open);
        menuButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.style.overflow = open && window.innerWidth <= 960 ? 'hidden' : '';
    }

    window.toggleSidebar = function () { setSidebar(!sidebar?.classList.contains('is-open')); };
    window.closeSidebar = function () { setSidebar(false); };

    menuButton?.addEventListener('click', window.toggleSidebar);
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-close-sidebar]')) setSidebar(false);
        if (window.innerWidth <= 960 && event.target.closest('.ms-nav-link')) setSidebar(false);
    });
    window.addEventListener('resize', () => { if (window.innerWidth > 960) setSidebar(false); });

    window.shopToast = function (message, type = 'ok', timeout = 3200) {
        let stack = document.getElementById('shopToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'shopToastStack';
            stack.className = 'ms-toast-stack';
            body.appendChild(stack);
        }
        const toast = document.createElement('div');
        toast.className = 'ms-toast ' + (type === 'error' ? 'is-error' : 'is-ok');
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.textContent = message;
        stack.appendChild(toast);
        window.setTimeout(() => toast.remove(), timeout);
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-open-dialog]');
        if (opener) {
            event.preventDefault();
            const dialog = document.getElementById(opener.dataset.openDialog);
            if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
        }
        const closer = event.target.closest('[data-close-dialog]');
        if (closer) {
            event.preventDefault();
            closer.closest('dialog')?.close();
        }
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
            if (outside) dialog.close();
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-table-filter]').forEach((input) => {
        const table = document.querySelector(input.dataset.tableFilter);
        if (!table) return;
        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach((row) => {
                if (row.classList.contains('ms-no-filter')) return;
                row.hidden = Boolean(query) && !row.innerText.toLowerCase().includes(query);
            });
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setSidebar(false);
    });
})();
