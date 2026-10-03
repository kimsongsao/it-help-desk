import '@tabler/core/dist/js/tabler.esm.min.js';
import { initAvatarCropper } from './avatar-cropper.js';
import { initComposers } from './ticket-composer.js';

/**
 * Ask for confirmation before submitting forms marked with data-confirm,
 * and show a spinner on the submit button so a form can't be sent twice.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }

    if (form.method.toLowerCase() === 'get') {
        return;
    }

    const button = event.submitter ?? form.querySelector('[type="submit"]');
    if (button && !button.disabled) {
        window.setTimeout(() => {
            button.disabled = true;
            button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" role="status"></span>');
        });
    }
});

/**
 * Make table rows with data-href behave like links, without hijacking clicks on real controls inside them.
 */
document.addEventListener('click', (event) => {
    const row = event.target.closest('tr[data-href]');
    if (!row || event.target.closest('a, button, input, select, label, form')) {
        return;
    }

    if (event.metaKey || event.ctrlKey) {
        window.open(row.dataset.href, '_blank');
    } else {
        window.location.href = row.dataset.href;
    }
});

/**
 * Light / dark theme toggle, remembered per browser.
 */
document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const theme = document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.bsTheme = theme;
        try {
            localStorage.setItem('theme', theme);
        } catch {
            // Storage can be unavailable in private windows; the toggle still works for this page.
        }
    });
});

/**
 * Show / hide password fields.
 */
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('.input-group').querySelector('input');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.querySelector('.ti').className = `ti ${isHidden ? 'ti-eye-off' : 'ti-eye'}`;
    });
});

/**
 * Telegram login: open Telegram's official authorization popup, then hand the signed data to our callback.
 */
document.querySelectorAll('[data-telegram-login]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!window.Telegram?.Login) {
            return;
        }

        window.Telegram.Login.auth({ bot_id: button.dataset.botId, request_access: 'write' }, (data) => {
            if (data) {
                window.location.href = `${button.dataset.callback}?${new URLSearchParams(data)}`;
            }
        });
    });
});

/**
 * Tell the server which time zone the browser is in, so dates can be shown in local time.
 */
document.querySelectorAll('[data-browser-timezone]').forEach((input) => {
    input.value = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';
});

initComposers();
initAvatarCropper();
