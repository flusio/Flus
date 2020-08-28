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
        fetch(destination)
            .then((response) => {
                return response.text();
            })
            .then((output) => {
                const parser = new DOMParser();
                const html = parser.parseFromString(output, 'text/html');
                const content = html.getElementById('modal-content');

                const updateModalEvent = new CustomEvent('update-modal', {
                    detail: {
                        content: content.innerHTML,
                        src: this.element,
                    },
                });
                modal.dispatchEvent(updateModalEvent);
            });
    }
};
