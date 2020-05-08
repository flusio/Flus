import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        if (document.documentElement.hasAttribute('data-turbolinks-preview')) {
            return;
        }
        console.log('Hello, Stimulus!', this.element);
    }
};
