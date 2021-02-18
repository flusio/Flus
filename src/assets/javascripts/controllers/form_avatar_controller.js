import { Controller } from 'stimulus';

export default class extends Controller {
    static get targets () {
        return ['file'];
    }

    openFile (event) {
        this.fileTarget.click();
    }
}
