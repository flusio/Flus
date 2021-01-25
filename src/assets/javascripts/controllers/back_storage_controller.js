import { Controller } from 'stimulus';

export default class extends Controller {
    initialize () {
        const type = this.data.get('type');
        const currentPath = window.location.pathname + window.location.search;
        window.localStorage.setItem('back-' + type, currentPath);
    }
};
