import { Controller } from 'stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return ['data', 'list', 'select'];
    }

    connect () {
        this.refreshList();
        this.refreshSelect();
    }

    refreshList () {
        let html = '';
        for (const option of this.dataTarget.selectedOptions) {
            html += this._item(option);
        }
        this.listTarget.innerHTML = html;
    }

    refreshSelect () {
        // remove all the options except the first one ('Attach a collection')
        while (this.selectTarget.options.length > 1) {
            this.selectTarget.remove(1);
        }

        // readd options that have not been selected yet
        for (const option of this.dataTarget.options) {
            if (!option.selected) {
                const newOption = new Option(option.text, option.value);
                this.selectTarget.add(newOption);
            }
        }

        // force the selection of the first option
        this.selectTarget.options[0].selected = true;

        // hide the select input if all collections have been selected
        if (this.selectTarget.options.length === 1) {
            this.selectTarget.style.display = 'none';
        } else {
            this.selectTarget.style.display = 'block';
        }

        // make the select required if no options have been selected and data
        // target have been marked as required.
        if (this.dataTarget.selectedOptions.length === 0) {
            this.selectTarget.required = this.dataTarget.required;
        } else {
            this.selectTarget.required = false;
        }
    }

    attach (event) {
        event.preventDefault();

        const value = event.target.value;
        for (const option of this.dataTarget.options) {
            if (option.value === value) {
                option.selected = true;
                this.refreshList();
                this.refreshSelect();
                this.selectTarget.focus();
                break;
            }
        }
    }

    detach (event) {
        event.preventDefault();

        const value = event.target.getAttribute('data-value');
        for (const option of this.dataTarget.selectedOptions) {
            if (option.value === value) {
                option.selected = false;
                this.refreshList();
                this.refreshSelect();
                this.selectTarget.focus();
                break;
            }
        }
    }

    _item (option) {
        return `
            <li class="collections-selector__item">
                <span class="collections-selector__item-label">
                    ${option.text}
                </span>

                <button
                    class="collections-selector__unselect button--smaller button--ghost"
                    type="button"
                    data-action="collections-selector#detach"
                    data-value="${option.value}"
                    title="${_('Unselect this collection')}"
                    aria-label="${_('Unselect')}"
                >
                    <i class="icon icon--only icon--times"></i>
                </button>
            </li>
        `;
    }
};
