import { Controller } from 'stimulus';

export default class extends Controller {
    initialize () {
        const type = this.data.get('type');
        const currentPath = window.location.pathname;
        window.localStorage.setItem('back-' + type, currentPath);
    }
};
