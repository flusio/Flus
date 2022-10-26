import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';
import icon from 'js/icon.js';

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
        // Reset the options and optgroups of the select
        this.selectTarget.innerHTML = '';

        const newOption = document.createElement('option');
        newOption.text = _('Select a collection');
        newOption.disabled = true;
        newOption.selected = true;
        this.selectTarget.add(newOption);

        // read options that have not been selected yet
        const optionsNoGroup = this.dataTarget.querySelectorAll('select > option');
        for (const option of optionsNoGroup) {
            if (!option.selected) {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                if ('public' in option.dataset) {
                    newOption.text += _(' (public)');
                }
                this.selectTarget.add(newOption);
            }
        }

        // same with the options in optgroups
        const groups = this.dataTarget.querySelectorAll('select > optgroup');
        for (const group of groups) {
            const newOptGroup = document.createElement('optgroup');
            newOptGroup.label = group.label;

            let groupIsEmpty = true;
            const groupOptions = group.querySelectorAll('optgroup > option');
            for (const option of groupOptions) {
                if (!option.selected) {
                    const newOption = document.createElement('option');
                    newOption.value = option.value;
                    newOption.text = option.text;
                    if ('public' in option.dataset) {
                        newOption.text += _(' (public)');
                    }
                    newOptGroup.append(newOption);
                    groupIsEmpty = false;
                }
            }

            if (!groupIsEmpty) {
                this.selectTarget.add(newOptGroup);
            }
        }

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

        const value = event.currentTarget.getAttribute('data-value');
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
        let publicNode = '';
        if ('public' in option.dataset) {
            publicNode = `<span class="sticker">${_('public')}</span>`;
        }

        return `
            <li class="collections-selector__item">
                <span class="collections-selector__item-label">
                    ${option.text}
                </span>

                ${publicNode}

                <button
                    class="collections-selector__unselect button--smaller button--ghost"
                    type="button"
                    data-action="collections-selector#detach"
                    data-value="${option.value}"
                    title="${_('Unselect this collection')}"
                    aria-label="${_('Unselect')}"
                >
                    ${icon('times')}
                </button>
            </li>
        `;
    }
};
