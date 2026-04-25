import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    async submit() {
        const button = this.element;
        button.disabled = true;

        const token = localStorage.getItem('token');

        try {
            const response = await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    Authorization: 'Bearer ' + token,
                },
            });

            if (response.status === 204 || response.status === 401) {
                localStorage.removeItem('token');
                window.location.href = '/login';
            } else {
                button.disabled = false;
                this._showError('Logout failed. Please try again.');
            }
        } catch (e) {
            button.disabled = false;
            this._showError('Network error. Please try again.');
        }
    }

    _showError(message) {
        let errorEl = this.element.parentElement.querySelector('[data-logout-error]');
        if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.setAttribute('data-logout-error', '');
            this.element.parentElement.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }
}
