import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return [
            'dataCollections',
            'dataNewCollections',
            'selectGroup',
            'inputGroup',
            'collectionCards',
            'select',
            'input',
            'collectionTemplate',
        ];
    }

    connect () {
        this.inputTarget.addEventListener('keydown', this.trapEscape.bind(this));
        this.inputTarget.addEventListener('keydown', this.trapEnter.bind(this));

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

        for (const input of this.dataNewCollectionsTarget.children) {
            this.collectionCardsTarget.appendChild(
                this.collectionNode(input.value, {
                    name: input.value,
                    imageFilename: '',
                    isPublic: false,
                }, true)
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
            this.selectTarget.disabled = true;
        } else {
            this.selectTarget.disabled = false;
        }

        // make the select required if no options have been selected and data
        // target have been marked as required.
        if (this.dataCollectionsTarget.selectedOptions.length === 0) {
            this.selectTarget.required = this.dataCollectionsTarget.required;
        } else {
            this.selectTarget.required = false;
        }
    }

    showInput () {
        this.inputGroupTarget.hidden = false;
        this.selectGroupTarget.hidden = true;
        this.inputTarget.focus();
    }

    showSelect () {
        this.selectGroupTarget.hidden = false;
        this.inputGroupTarget.hidden = true;
        this.selectTarget.focus();
    }

    setFocus () {
        if (this.selectGroupTarget.hidden) {
            this.inputTarget.focus();
        } else {
            this.selectTarget.focus();
        }
    }

    attach (event) {
        event.preventDefault();

        if (this.selectGroupTarget.hidden) {
            const value = this.inputTarget.value;
            if (value !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'new_collection_names[]';
                input.value = value;
                this.dataNewCollectionsTarget.append(input);
            }
            this.inputTarget.value = '';
        } else {
            const value = event.target.value;
            for (const option of this.dataCollectionsTarget.options) {
                if (option.value === value) {
                    option.selected = true;
                    this.refreshSelect();
                    break;
                }
            }
        }

        this.refreshList();
        this.setFocus();
    }

    detach (event) {
        event.preventDefault();

        const value = event.currentTarget.getAttribute('data-value');
        const isNew = event.currentTarget.getAttribute('data-is-new');

        if (isNew === 'true') {
            for (const input of this.dataNewCollectionsTarget.children) {
                if (input.value === value) {
                    input.remove();
                    break;
                }
            }
        } else {
            for (const option of this.dataCollectionsTarget.selectedOptions) {
                if (option.value === value) {
                    option.selected = false;
                    this.refreshSelect();
                    break;
                }
            }
        }

        this.refreshList();
        this.setFocus();
    }

    trapEscape (event) {
        if (event.key === 'Escape') {
            event.stopPropagation(); // avoid to close the modal
            event.preventDefault();
            this.showSelect();
        }
    }

    trapEnter (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.attach(event);
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

        if (!isNew) {
            item.querySelector('[data-target="isNew"]').remove();
        }

        const unselectButton = item.querySelector('[data-target="unselect"]');
        unselectButton.setAttribute('data-value', value);
        unselectButton.setAttribute('data-is-new', isNew);

        return item;
    }
};
