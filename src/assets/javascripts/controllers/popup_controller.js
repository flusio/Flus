import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect () {
        const summaryElement = this.element.querySelector('summary');
        if (summaryElement) {
            summaryElement.setAttribute('aria-haspopup', 'menu');
            summaryElement.setAttribute('aria-expanded', this.element.open);
        }
    }

    update (e) {
        const summaryElement = this.element.querySelector('summary');
        if (summaryElement) {
            summaryElement.setAttribute('aria-expanded', this.element.open);
        }
    }

    closeOnClickOutside (e) {
        if (this.element.contains(e.target)) {
            // the user clicked on an element inside the popup menu
            return;
        }

        if (!this.element.open) {
            return;
        }

        this.element.open = false;
    }

    closeOnEscape (e) {
        if (event.key !== 'Escape') {
            return;
        }

        if (!this.element.open) {
            return;
        }

        this.element.open = false;

        const summaryElement = this.element.querySelector('summary');
        if (summaryElement) {
            summaryElement.focus();
        }
    }
};
