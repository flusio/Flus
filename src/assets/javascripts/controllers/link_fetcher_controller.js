import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const form = this.element;
        if (form) {
            form.style.display = 'none';
            window.setTimeout(() => {
                form.submit();
            }, 2000);
        }
    }
};
