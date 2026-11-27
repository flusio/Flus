import { Controller } from '@hotwired/stimulus';

// Give the focus back to a Turbo frame once its content is replaced.
export default class extends Controller {
    static targets = ['focus'];

    remember () {
        this.hadFocus = this.hadFocus || this.element.contains(document.activeElement);
    }

    restore () {
        const hadFocus = this.hadFocus;
        this.hadFocus = false;

        if (hadFocus && !this.element.contains(document.activeElement)) {
            this.focusTarget.focus();
        }
    }
}
