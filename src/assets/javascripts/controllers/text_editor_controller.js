import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        this.element.style.overflowY = 'hidden';
        this.element.style.resize = 'none';
        this.refresh();
    }

    refresh () {
        this.element.style.height = 'auto';
        this.element.style.height = (this.element.scrollHeight) + 'px';
    }
};
