import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    initialize () {
        this.namespace = 'autosave:' + this.element.action;
    }

    connect () {
        const data = this.loadData(this.namespace);

        Object.entries(data).forEach(([name, value]) => {
            const element = this.element.querySelector(`[name="${name}"]`);
            if (element) {
                element.value = value;
            }
        });
    }

    save (event) {
        clearTimeout(this.debounceTimer);

        this.debounceTimer = setTimeout(() => {
            const data = this.loadData(this.namespace);

            data[event.target.name] = event.target.value;

            window.localStorage.setItem(this.namespace, JSON.stringify(data));
        }, 300);
    }

    loadData (namespace) {
        const rawData = window.localStorage.getItem(namespace);

        let data = {};
        try {
            data = JSON.parse(rawData);
        } catch {
            return {};
        }

        if (
            typeof data !== 'object' ||
            Array.isArray(data) ||
            data === null
        ) {
            return {};
        }

        return data;
    }

    clear () {
        window.localStorage.removeItem(this.namespace);
    }
};
