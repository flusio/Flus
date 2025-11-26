// This file is part of Flus.
// Copyright 2022-2025 Probesys (Bileto)
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect () {
        const openerElement = this.element.querySelector('.popup__opener');
        if (openerElement) {
            openerElement.setAttribute('aria-haspopup', 'menu');
            openerElement.setAttribute('aria-expanded', this.element.open);
        }

        const containerElement = this.element.querySelector('.popup__container');
        if (containerElement) {
            containerElement.setAttribute('role', 'menu');
        }

        const itemsElements = this.element.querySelectorAll('.popup__item');
        itemsElements.forEach((element) => {
            element.setAttribute('role', 'menuitem');
        });

        this.element.addEventListener('keydown', this.closeOnEscape.bind(this));
        this.element.addEventListener('keydown', this.toggleMenuOnKeydown.bind(this));
        this.element.addEventListener('keydown', this.navigateInMenuOnArrow.bind(this));
    }

    /**
     * Update the aria-expanded attribute on toggle.
     */
    update () {
        const openerElement = this.element.querySelector('.popup__opener');
        if (openerElement) {
            openerElement.setAttribute('aria-expanded', this.element.open);
        }
    }

    /**
     * Close the menu.
     */
    close () {
        if (!this.element.open) {
            return;
        }

        this.element.open = false;

        const openerElement = this.element.querySelector('.popup__opener');
        if (openerElement) {
            openerElement.focus();
        }
    }

    /**
     * Close the menu when clicking on an element outside of the current popup.
     */
    closeOnClickOutside (event) {
        if (this.element.contains(event.target)) {
            // The user clicked on an element inside the popup menu.
            return;
        }

        if (!this.element.open) {
            return;
        }

        this.element.open = false;
    }

    /**
     * Close the menu on Escape keydown.
     */
    closeOnEscape (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (!this.element.open) {
            return;
        }

        this.element.open = false;

        const openerElement = this.element.querySelector('.popup__opener');
        if (openerElement) {
            openerElement.focus();
        }
    }

    /**
     * Toggle the menu when activating the popup__opener with keyboard, and set
     * the focus on the first element if the menu is opened.
     */
    toggleMenuOnKeydown (event) {
        const openerElement = this.element.querySelector('.popup__opener');
        if (!openerElement || event.target !== openerElement) {
            return;
        }

        if (event.code !== 'Enter' && event.code !== 'Space') {
            return;
        }

        event.preventDefault();

        this.element.open = !this.element.open;

        if (this.element.open) {
            const itemElement = this.element.querySelector('.popup__item');
            if (itemElement) {
                itemElement.focus();
            }
        }
    }

    /**
     * Handle the navigation in the menu with the arrows.
     */
    navigateInMenuOnArrow (event) {
        if (!this.element.open) {
            return;
        }

        if (event.code !== 'ArrowDown' && event.code !== 'ArrowUp') {
            return;
        }

        event.preventDefault();

        // Get the current focused element from the popup__items (if any).
        const itemsElements = this.element.querySelectorAll('.popup__item');
        let focusedElementKey = null;
        let focusedElement = null;
        for (const [key, element] of itemsElements.entries()) {
            // If the focused element is a radio button, the popup__item will
            // be its label, so we need to check for its "for" attribute.
            if (element === document.activeElement || element.getAttribute('for') === document.activeElement.id) {
                focusedElementKey = key;
                focusedElement = element;
                break;
            }
        }

        if (event.code === 'ArrowDown') {
            // When pressing ArrowDown, focus the element after the actual
            // focused element. If there are no focused element or we are
            // already at the end of the list, focus the first element.
            if (
                !focusedElement ||
                focusedElementKey === itemsElements.length - 1
            ) {
                itemsElements[0].focus();
            } else {
                itemsElements[focusedElementKey + 1].focus();
            }
        } else if (event.code === 'ArrowUp') {
            // When pressing ArrowUp, focus the element before the actual
            // focused element. If there are no focused element or we are
            // already at the beginning of the list, focus the last element.
            if (
                !focusedElement ||
                focusedElementKey === 0
            ) {
                itemsElements[itemsElements.length - 1].focus();
            } else {
                itemsElements[focusedElementKey - 1].focus();
            }
        }
    }
};
