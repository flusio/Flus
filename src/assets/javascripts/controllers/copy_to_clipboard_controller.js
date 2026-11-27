import { Controller } from '@hotwired/stimulus';

import _ from '../l10n.js';
import icon from '../icon.js';

export default class extends Controller {
    static get targets () {
        return ['copyable', 'feedback'];
    }

    static values = {
        format: { type: String, default: 'text' },
    };

    copy () {
        let text;
        if (this.copyableTarget.hasAttribute('value')) {
            text = this.copyableTarget.getAttribute('value').trim();
        } else {
            text = this.copyableTarget.textContent.trim();
        }

        if (this.formatValue === 'html' && window.ClipboardItem) {
            // The plain text version keeps the HTML source, so pasting in a
            // field which doesn't accept rich text doesn't paste nothing.
            const item = new ClipboardItem({
                'text/html': new Blob([text], { type: 'text/html' }),
                'text/plain': new Blob([text], { type: 'text/plain' }),
            });
            navigator.clipboard.write([item]);
        } else {
            navigator.clipboard.writeText(text);
        }

        const oldFeedbackTargetText = this.feedbackTarget.innerHTML;
        this.feedbackTarget.innerHTML = icon('check') + ' ' + _('Copied');

        setTimeout(() => {
            this.feedbackTarget.innerHTML = oldFeedbackTargetText;
        }, 2000);
    }
};
