import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        // We add the csrf token with JavaScript to hopefully block bots which
        // spam registration. Since JS is required to use flusio, it should be
        // transparent for users.
        const csrfName = document.querySelector('meta[name="csrf-param"]').content;
        const csrfValue = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.setAttribute('type', 'hidden');
        csrfInput.setAttribute('name', csrfName);
        csrfInput.setAttribute('value', csrfValue);
        this.element.appendChild(csrfInput);
    }
};
