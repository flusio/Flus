import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'prototype']

    static values = {
        index: Number,
    }

    connect () {
        this.refreshLabels();
    }

    addItem () {
        const element = this.prototypeTarget.content.firstElementChild.cloneNode(true);
        element.innerHTML = element.innerHTML.replace(/__index__/g, this.indexValue);

        this.containerTarget.appendChild(element);

        this.indexValue++;

        this.refreshLabels();

        const focusableElements = Array.from(element.querySelectorAll('textarea'));
        if (focusableElements.length >= 1) {
            focusableElements[0].focus();
        }
    }

    removeItem (event) {
        const target = event.target;
        const element = target.closest('[data-items-target="item"]');

        element.remove();

        this.refreshLabels();
    }

    refreshLabels () {
        const elementsWithArialLabel = this.containerTarget.querySelectorAll('[aria-label]');
        elementsWithArialLabel.forEach((element, index) => {
            let labelPattern = element.dataset.labelPattern;

            if (!labelPattern) {
                // First time we refresh the labels, we save the content of
                // labels as patterns.
                labelPattern = element.ariaLabel;
                element.dataset.labelPattern = labelPattern;
            }

            // Update the labels with the correct number.
            element.ariaLabel = labelPattern.replace(/__number__/, index + 1);
        });
    }
}
