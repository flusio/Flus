import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['switch', 'caption'];
    }

    connect () {
        this.switch();
    }

    switch () {
        const checkedElement = this.switchTargets.find(element => element.checked);

        if (checkedElement) {
            const captionValue = checkedElement.value;
            this.captionTargets.forEach((element) => {
                element.hidden = element.dataset.captionValue !== captionValue;
            });
        } else {
            this.captionTargets.forEach((element) => {
                element.hidden = element.dataset.captionUnchecked == null;
            });
        }
    }
}
