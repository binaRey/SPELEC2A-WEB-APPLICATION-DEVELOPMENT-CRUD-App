/**
 * UI behaviour: mobile menu, dismissible alerts, delete confirmation dialog.
 * All features degrade gracefully when JavaScript is unavailable.
 */
(() => {
    'use strict';

    // Mobile navigation toggle
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(open));
        });
    }

    // Dismiss flash messages
    document.addEventListener('click', (e) => {
        const close = e.target.closest('.alert-close');
        if (close) close.closest('.alert').classList.add('hide');
    });

    // Delete confirmation (uses <dialog>, falls back to window.confirm)
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
})();
