import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        mode: String,
        timeout: Number,
    }

    connect () {
        if (this.modeValue === 'timeout') {
            let timeout;
            if (this.hasTimeoutValue) {
                timeout = this.timeoutValue;
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
