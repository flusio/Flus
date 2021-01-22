import { Controller } from 'stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return ['copyable', 'feedback'];
    }

    copy (event) {
        let text;
        if (this.copyableTarget.hasAttribute('value')) {
            text = this.copyableTarget.getAttribute('value').trim();
        } else {
            text = this.copyableTarget.textContent.trim();
        }

        navigator.clipboard.writeText(text);
        this.element.classList.add('copy--success');

        const oldFeedbackTargetText = this.feedbackTarget.textContent;
        this.feedbackTarget.textContent = _('Copied');

        setTimeout(() => {
            this.element.classList.remove('copy--success');
            this.feedbackTarget.textContent = oldFeedbackTargetText;
        }, 2000);
    }
};
