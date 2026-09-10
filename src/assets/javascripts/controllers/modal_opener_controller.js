import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        selector: String,
    };

    // Open the modal with a content fetched from the URL given in the
    // selector value.
    fetch (event) {
        event.preventDefault();
        this.dispatchOpenModalEvent('fetch');
    }

    // Open the modal with a content copied from the <template> element
    // matched by the selector value.
    copy (event) {
        event.preventDefault();
        this.dispatchOpenModalEvent('copy');
    }

    dispatchOpenModalEvent (mode) {
        const modal = document.getElementById('modal');
        const openModalEvent = new CustomEvent('open-modal', {
            detail: {
                target: this.element,
                mode: mode,
                selector: this.selectorValue,
            },
        });
        modal.dispatchEvent(openModalEvent);
    }
};
