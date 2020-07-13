import { Controller } from 'stimulus';

export default class extends Controller {
    confirm (event) {
        const message = event.target.dataset.message;
        if (!confirm(message)) {
            event.preventDefault();
        }
    }
};
