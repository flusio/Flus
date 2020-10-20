import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const type = this.data.get('type');
        const replace = this.data.get('replace');
        const mode = this.data.get('mode');
        const backUrl = window.localStorage.getItem('back-' + type);
        if (replace && backUrl) {
            this.element.setAttribute(replace, backUrl);
        }

        if (!backUrl && mode === 'hide-if-none') {
            this.element.remove();
        }
    }
};
