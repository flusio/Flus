import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['input', 'message', 'suggestion'];
    }

    change (event) {
        event.preventDefault();

        this.inputTarget.value = this.suggestionTarget.innerHTML;
        this.messageTarget.hidden = true;
        this.inputTarget.focus();
    }
}
