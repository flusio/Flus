import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        type: String,
        replace: String,
        mode: String,
    }

    connect () {
        const type = this.typeValue;
        const replace = this.replaceValue;
        const mode = this.modeValue;
        const backUrl = window.localStorage.getItem('back-' + type);
        if (replace && backUrl) {
            this.element.setAttribute(replace, backUrl);
        }

        if (!backUrl && mode === 'hide-if-none') {
            this.element.remove();
        }
    }
};
