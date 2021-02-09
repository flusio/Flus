import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const mode = this.data.get('mode');
        if (mode === 'timeout') {
            window.setTimeout(() => {
                this.submit();
            }, 500);
        }
    }

    submit (event) {
        this.element.submit();
    }
};
