import { Controller } from 'stimulus';

export default class extends Controller {
    close (e) {
        if (!this.element.contains(e.target)) {
            this.element.open = false;
        }
    }
};
