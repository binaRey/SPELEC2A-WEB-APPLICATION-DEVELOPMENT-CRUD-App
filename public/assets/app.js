/**
 * UI behaviour: mobile menu, dismissible alerts, delete confirmation dialog,
 * focus on validation errors, double-submit protection.
 * Everything degrades gracefully when JavaScript is unavailable.
 */
(() => {
    'use strict';

    /* ---------------- Mobile navigation ---------------- */
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('site-nav');

    const setNav = (open) => {
        if (!toggle || !nav) return;
        nav.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };

    if (toggle && nav) {
        toggle.addEventListener('click', () => setNav(!nav.classList.contains('open')));

        // Escape closes the menu and returns focus to the toggle button.
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && nav.classList.contains('open')) {
                setNav(false);
                toggle.focus();
            }
        });

        // Clicking outside closes it.
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !toggle.contains(e.target)) setNav(false);
        });
    }

    /* ---------------- Dismiss flash messages ---------------- */
    document.addEventListener('click', (e) => {
        const close = e.target.closest('.alert-close');
        if (close) close.closest('.alert').classList.add('hide');
    });

    /* ---------------- Move focus to validation errors ---------------- */
    const summary = document.getElementById('error-summary');
    if (summary) summary.focus();

    /* ---------------- Delete confirmation (<dialog>, falls back to confirm()) ---------------- */
    const dialog = document.getElementById('confirm-dialog');
    let pendingForm = null;

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirm]');
        if (!form || form.dataset.confirmed === '1') return;

        e.preventDefault();
        const name = form.dataset.name || 'this user';

        if (dialog && typeof dialog.showModal === 'function') {
            pendingForm = form;
            dialog.querySelector('#confirm-name').textContent = name;
            dialog.showModal();
        } else if (window.confirm(`Delete ${name}?`)) {
            form.dataset.confirmed = '1';
            form.submit();
        }
    });

    if (dialog) {
        dialog.querySelector('[data-dialog-cancel]').addEventListener('click', () => {
            pendingForm = null;
            dialog.close();
        });
        dialog.querySelector('[data-dialog-confirm]').addEventListener('click', () => {
            if (pendingForm) {
                pendingForm.dataset.confirmed = '1';
                pendingForm.submit();
            }
            dialog.close();
        });
    }

    /* ---------------- Prevent double submits ---------------- */
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return; // e.g. waiting for the delete confirmation
        const button = e.target.querySelector('button[type="submit"]');
        if (!button) return;
        // Disable after the browser has collected the form data.
        setTimeout(() => {
            button.disabled = true;
            button.dataset.busy = '1';
        }, 0);
    });

    // Re-enable buttons when the user comes back with the Back button (bfcache).
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('button[data-busy]').forEach((b) => {
            b.disabled = false;
            delete b.dataset.busy;
        });
    });
})();
