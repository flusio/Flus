import { Controller } from 'stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return ['input'];
    }

    connect () {
        const jsOnlyElement = this.element.querySelector('.js-only');
        if (jsOnlyElement) {
            jsOnlyElement.classList.remove('js-only');
        }
    }

    toggle (e) {
        const currentType = this.inputTarget.type;
        if (currentType === 'password') {
            this.inputTarget.type = 'text';
            e.target.innerHTML = _('Hide');
        } else {
            this.inputTarget.type = 'password';
            e.target.innerHTML = _('Show');
        }
    }
};
