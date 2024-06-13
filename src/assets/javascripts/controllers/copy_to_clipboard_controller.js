import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';
import icon from 'js/icon.js';

export default class extends Controller {
    static get targets () {
        return ['copyable', 'feedback'];
    }

    copy () {
        let text;
        if (this.copyableTarget.hasAttribute('value')) {
            text = this.copyableTarget.getAttribute('value').trim();
        } else {
            text = this.copyableTarget.textContent.trim();
        }

        navigator.clipboard.writeText(text);

        const oldFeedbackTargetText = this.feedbackTarget.innerHTML;
        this.feedbackTarget.innerHTML = icon('check') + ' ' + _('Copied');

        setTimeout(() => {
            this.feedbackTarget.innerHTML = oldFeedbackTargetText;
        }, 2000);
    }
};
