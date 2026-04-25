import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['email', 'password', 'error', 'submit'];

    async submit(event) {
        event.preventDefault();
        this.submitTarget.disabled = true;
        this._clearError();

        try {
            const response = await fetch('/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: this.emailTarget.value,
                    password: this.passwordTarget.value,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                localStorage.setItem('token', data.token);
                window.location.href = '/';
            } else if (response.status >= 400) {
                this._showError(data.error ?? 'Login failed. Please try again.');
                this.submitTarget.disabled = false;
            }
        } catch (e) {
            this._showError('Network error. Please try again.');
            this.submitTarget.disabled = false;
        }
    }

    _showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }

    _clearError() {
        this.errorTarget.textContent = '';
        this.errorTarget.hidden = true;
    }
}
