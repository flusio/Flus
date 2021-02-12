import { Controller } from 'stimulus';

export default class extends Controller {
    connect () {
        const mode = this.data.get('mode');
        if (mode === 'timeout') {
            let timeout;
            if (this.data.has('timeout')) {
                timeout = parseInt(this.data.get('timeout'), 10);
            } else {
                timeout = 500;
            }
            window.setTimeout(() => {
                this.submit();
            }, timeout);
        }
    }

    submit (event) {
        this.element.submit();
    }
};
