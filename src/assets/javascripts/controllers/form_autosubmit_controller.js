import { Controller } from 'stimulus';

export default class extends Controller {
    static get targets () {
        return ['subject'];
    }

    connect () {
        const submitButton = this.element.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.remove();
        }
    }

    submit (e) {
        this.element.submit();
    }
};
