import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['input', 'message'];
    }

    static values = {
        url: String,
    };

    change () {
        this.inputTarget.value = this.urlValue;
        this.messageTarget.hidden = true;
        this.inputTarget.focus();
    }
}
