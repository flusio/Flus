import { Controller } from 'stimulus';

export default class extends Controller {
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

        const destination = this.data.get('href');
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
