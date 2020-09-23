import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const type = this.data.get('type');
        const backUrl = window.localStorage.getItem('back-' + type);
        if (backUrl) {
            this.element.setAttribute('href', backUrl);
        }
    }
};
