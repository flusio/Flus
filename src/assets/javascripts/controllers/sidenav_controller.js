import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['opener', 'closer'];
    }

    open () {
        this.openerTarget.setAttribute('aria-expanded', 'true');
        this.closerTarget.focus();
    }

    close () {
        this.openerTarget.setAttribute('aria-expanded', 'false');
        this.openerTarget.focus();
    }

    closeOnMask (event) {
        // This allows to close the menu by clicking outside it
        if (event.target.classList.contains('sidenav__container')) {
            this.close(event);
        }
    }
};
