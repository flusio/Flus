import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        message: String,
    }

    confirm (event) {
        if (!confirm(this.messageValue)) {
            event.preventDefault();
        }
    }
};
