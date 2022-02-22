import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    open (e) {
        this.element.open = true;
    }

    toggle (e) {
        this.element.open = !this.element.open;
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
};
