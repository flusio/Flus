import { Controller } from 'stimulus';

export default class extends Controller {
    initialize () {
        const types = this.data.get('type').split(',');
        const currentPath = window.location.pathname + window.location.search;
        types.forEach((type) => {
            window.localStorage.setItem('back-' + type.trim(), currentPath);
        });
    }
};
