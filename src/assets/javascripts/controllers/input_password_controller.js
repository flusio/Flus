import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';
import icon from 'js/icon.js';

export default class extends Controller {
    static get targets () {
        return ['input', 'button'];
    }

    connect () {
        const jsOnlyElement = this.element.querySelector('.js-only');
        if (jsOnlyElement) {
            jsOnlyElement.classList.remove('js-only');
        }
    }

    toggle () {
        const currentType = this.inputTarget.type;
        if (currentType === 'password') {
            this.inputTarget.type = 'text';
            this.buttonTarget.innerHTML = icon('eye-hide') + ' ' + _('Hide');
        } else {
            this.inputTarget.type = 'password';
            this.buttonTarget.innerHTML = icon('eye') + ' ' + _('Show');
        }
    }
};
