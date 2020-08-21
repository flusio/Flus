import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const backUrl = window.localStorage.getItem('backForLink');
        if (backUrl) {
            this.element.setAttribute('href', backUrl);
        }
    }
};
