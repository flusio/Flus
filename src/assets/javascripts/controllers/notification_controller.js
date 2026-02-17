import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect () {
        setTimeout(() => {
            this.element.remove();
        }, 10000);
    }

    close () {
        this.element.remove();
    }
};
