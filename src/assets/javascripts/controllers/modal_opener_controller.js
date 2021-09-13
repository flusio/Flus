import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        href: String,
    }

    fetch (event) {
        event.preventDefault();

        const modal = document.getElementById('modal');
        const openModalEvent = new CustomEvent('open-modal', {
            detail: {
                target: this.element,
                href: this.hrefValue,
            },
        });
        modal.dispatchEvent(openModalEvent);
    }
};
