import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const form = this.element;
        if (form) {
            window.setTimeout(() => {
                form.submit();
            }, 500);
        }
    }
};
