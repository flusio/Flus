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
                content: null,
                src: this.element,
            },
        });
        modal.dispatchEvent(openModalEvent);

        const destination = this.hrefValue;
        const init = {
            headers: new Headers({
                'X-Requested-With': 'XMLHttpRequest',
            }),
        };

        fetch(destination, init)
            .then((response) => {
                return response.text();
            })
            .then((output) => {
                const updateModalEvent = new CustomEvent('update-modal', {
                    detail: {
                        content: output,
                        src: this.element,
                    },
                });
                modal.dispatchEvent(updateModalEvent);
            });
    }
};
