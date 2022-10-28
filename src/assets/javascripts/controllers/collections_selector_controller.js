import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return [
            'dataCollections',
            'selectGroup',
            'collectionCards',
            'select',
            'collectionTemplate',
        ];
    }

    connect () {
        this.refreshList();
        this.refreshSelect();
    }

    refreshList () {
        this.collectionCardsTarget.innerHTML = '';
        for (const option of this.dataCollectionsTarget.selectedOptions) {
            this.collectionCardsTarget.appendChild(
                this.collectionNode(option.value, {
                    name: option.text,
                    imageFilename: option.dataset.illustration,
                    isPublic: 'public' in option.dataset,
                }, false)
            );
        }
    }

    refreshSelect () {
        // Reset the options and optgroups of the select
        this.selectTarget.innerHTML = '';

        const newOption = document.createElement('option');
        newOption.text = _('Open the list');
        newOption.disabled = true;
        newOption.selected = true;
        this.selectTarget.add(newOption);

        // read options that have not been selected yet
        const optionsNoGroup = this.dataCollectionsTarget.querySelectorAll('select > option');
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
        const groups = this.dataCollectionsTarget.querySelectorAll('select > optgroup');
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
            this.selectTarget.parentElement.hidden = true;
        } else {
            this.selectTarget.parentElement.hidden = false;
        }

        // make the select required if no options have been selected and data
        // target have been marked as required.
        if (this.dataCollectionsTarget.selectedOptions.length === 0) {
            this.selectTarget.required = this.dataCollectionsTarget.required;
        } else {
            this.selectTarget.required = false;
        }
    }

    attach (event) {
        event.preventDefault();

        const value = event.target.value;
        for (const option of this.dataCollectionsTarget.options) {
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
        for (const option of this.dataCollectionsTarget.selectedOptions) {
            if (option.value === value) {
                option.selected = false;
                this.refreshList();
                this.refreshSelect();
                this.selectTarget.focus();
                break;
            }
        }
    }

    collectionNode (value, collection, isNew) {
        const item = this.collectionTemplateTarget.content.firstElementChild.cloneNode(true);

        item.querySelector('[data-target="name"]').textContent = collection.name;

        if (collection.imageFilename) {
            item.style.backgroundImage = `url('${collection.imageFilename}')`;
        }

        if (!collection.isPublic) {
            item.querySelector('[data-target="isPublic"]').remove();
        }

        const unselectButton = item.querySelector('[data-target="unselect"]');
        unselectButton.setAttribute('data-value', value);

        return item;
    }
};
