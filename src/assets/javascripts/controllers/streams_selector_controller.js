import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['checkbox', 'counter'];
    }

    connect () {
        this.refreshCounter();
    }

    refreshCounter () {
        const count = this.checkboxTargets.filter((checkbox) => checkbox.checked).length;
        this.counterTarget.textContent = count;
    }
}
