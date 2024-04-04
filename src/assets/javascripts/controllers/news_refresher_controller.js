import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
    };

    static get targets () {
        return ['button'];
    }

    connect () {
        this.checkAvailableNews();
        setInterval(this.checkAvailableNews.bind(this), 5 * 60 * 1000);
    }

    async checkAvailableNews () {
        const response = await fetch(this.urlValue);
        const data = await response.json();
        if (data.available) {
            this.buttonTarget.classList.add('button--primary');
        } else {
            this.buttonTarget.classList.remove('button--primary');
        }
    }
};
