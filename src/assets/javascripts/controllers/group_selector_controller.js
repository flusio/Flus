import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['groupInput', 'groupSelect', 'input', 'select'];
    }

    connect () {
        if (this.selectTarget.options.length === 1) {
            this.showInput();
        }
    }

    showInput () {
        this.inputTarget.name = 'name';
        this.selectTarget.name = '';

        this.groupInputTarget.hidden = false;
        this.groupSelectTarget.hidden = true;

        this.inputTarget.focus();
    }
}
