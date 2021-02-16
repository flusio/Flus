import { Controller } from 'stimulus';

export default class extends Controller {
    static get targets () {
        return ['checkbox', 'captionTrue', 'captionFalse'];
    }

    connect () {
        this.changeCaption();
    }

    changeCaption () {
        if (this.checkboxTarget.checked) {
            this.showCaptionTrue();
        } else {
            this.showCaptionFalse();
        }
    }

    showCaptionTrue () {
        this.captionTrueTarget.style.display = 'initial';
        this.captionFalseTarget.style.display = 'none';
    }

    showCaptionFalse () {
        this.captionTrueTarget.style.display = 'none';
        this.captionFalseTarget.style.display = 'initial';
    }
}
