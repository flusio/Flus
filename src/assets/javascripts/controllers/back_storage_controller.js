import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        type: String,
    }

    initialize () {
        const types = this.typeValue.split(',');
        const currentPath = window.location.pathname + window.location.search;
        types.forEach((type) => {
            window.localStorage.setItem('back-' + type.trim(), currentPath);
        });
    }
};
