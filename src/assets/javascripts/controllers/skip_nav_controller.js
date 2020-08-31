import { Controller } from 'stimulus';

export default class extends Controller {
    skip (event) {
        event.preventDefault();

        const id = this.element.hash;
        document.querySelector(id).focus();
    }
};
