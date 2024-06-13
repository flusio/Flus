import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['file'];
    }

    openFile () {
        this.fileTarget.click();
    }
}
