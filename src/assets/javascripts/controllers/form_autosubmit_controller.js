import { Controller } from 'stimulus';

export default class extends Controller {
    static get targets () {
        return ['subject'];
    }

    submit (e) {
        this.element.submit();
    }
};
