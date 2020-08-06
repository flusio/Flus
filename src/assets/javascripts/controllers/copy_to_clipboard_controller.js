import { Controller } from 'stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return ['copyable', 'feedback'];
    }

    copy (event) {
        const text = this.copyableTarget.textContent.trim();
        navigator.clipboard.writeText(text);
        this.element.classList.add('copy--success');
        this.feedbackTarget.textContent = _('copied');

        setTimeout(() => {
            this.element.classList.remove('copy--success');
            this.feedbackTarget.textContent = _('copy');
        }, 2000);
    }
};
