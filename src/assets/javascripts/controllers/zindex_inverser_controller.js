import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['item'];
    }

    connect () {
        const baseZindex = 10;
        const zindexMax = this.itemTargets.length;
        this.itemTargets.forEach((item, index) => {
            item.style.zIndex = baseZindex + zindexMax - index;
        });
    }
}
