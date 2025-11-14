import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        name: String,
        token: String,
    }

    connect () {
        // We add the csrf token with JavaScript to hopefully block bots which
        // spam registration. Since JS is required to use Flus, it should be
        // transparent for users.
        const csrfInput = document.createElement('input');
        csrfInput.setAttribute('type', 'hidden');
        csrfInput.setAttribute('name', this.nameValue);
        csrfInput.setAttribute('value', this.tokenValue);
        this.element.appendChild(csrfInput);
    }
};
