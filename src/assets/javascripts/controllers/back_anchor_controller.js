import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const type = this.data.get('type');
        const replace = this.data.get('replace');
        const backUrl = window.localStorage.getItem('back-' + type);
        if (replace && backUrl) {
            this.element.setAttribute(replace, backUrl);
        }
    }
};
