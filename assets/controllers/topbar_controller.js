import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    async logout() {
        const token = localStorage.getItem('token');

        try {
            const response = await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    Authorization: 'Bearer ' + token,
                },
            });

            if (response.ok) {
                localStorage.removeItem('token');
                window.location.href = '/';
            } else {
                this._showError('Logout failed. Please try again.');
            }
        } catch (e) {
            this._showError('Network error. Please try again.');
        }
    }

    _showError(message) {
        let errorEl = this.element.parentElement.querySelector('[data-topbar-error]');
        if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.setAttribute('data-topbar-error', '');
            this.element.parentElement.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }
}
