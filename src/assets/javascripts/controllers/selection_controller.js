import { Controller } from '@hotwired/stimulus';

// Manage a selection of items via checkboxes, in order to apply actions to
// all of them at once.
//
// The selection happens in a dedicated mode, entered with the `start` button
// and left with a "cancel" button (see stop method). The mode is materialized
// by the `data-selection-mode` attribute on the controlled element, which
// drives the display via CSS.
//
// The `selectAll` checkbox checks or unchecks all the items in a single
// click. It only applies to the visible items, and an item hidden by a filter
// is unchecked (see uncheckHidden): the selection always corresponds to what
// the user sees.
//
// The `counter` target receives the number of checked items.
//
// The `button` targets (i.e. the buttons applying the actions) are disabled
// while the selection is empty.
//
// The selection is emptied on `turbo:morph` (see clear): the morphing
// preserves the state of the checkboxes, while an action just applied to the
// selection must reset it.
export default class extends Controller {
    static targets = ['checkbox', 'selectAll', 'counter', 'button', 'popup', 'start'];

    connect () {
        this.refresh();
    }

    start () {
        this.element.setAttribute('data-selection-mode', '');

        if (this.hasSelectAllTarget) {
            this.selectAllTarget.focus();
        }
    }

    stop () {
        this.element.removeAttribute('data-selection-mode');
        this.clear();

        if (this.hasStartTarget) {
            this.startTarget.focus();
        }
    }

    refresh () {
        const count = this.checkboxTargets.filter((checkbox) => checkbox.checked).length;

        if (this.hasCounterTarget) {
            this.counterTarget.textContent = count;
        }

        this.buttonTargets.forEach((button) => {
            button.disabled = count === 0;
        });

        // The popups cannot be disabled as the buttons are (their opener is a
        // <summary>): they are made inert instead, which blocks both the
        // pointer and the keyboard.
        this.popupTargets.forEach((popup) => {
            popup.toggleAttribute('inert', count === 0);

            if (count === 0) {
                const details = popup.querySelector('details[open]');

                if (details) {
                    details.removeAttribute('open');
                }
            }
        });

        if (this.hasSelectAllTarget) {
            const visibleCheckboxes = this.visibleCheckboxes();
            this.selectAllTarget.checked = (
                visibleCheckboxes.length > 0 &&
                visibleCheckboxes.every((checkbox) => checkbox.checked)
            );
        }
    }

    toggleAll () {
        const checked = this.selectAllTarget.checked;

        this.visibleCheckboxes().forEach((checkbox) => {
            checkbox.checked = checked;
        });

        this.refresh();
    }

    // Toggle the checkbox of the item that has been clicked: in selection
    // mode, the whole item is a click target, and a click intended for the
    // selection must not activate the links of the item.
    toggleItem (event) {
        if (!this.element.hasAttribute('data-selection-mode')) {
            return;
        }

        // A click on the checkbox or on its label already toggles it.
        if (event.target.closest('input, label')) {
            return;
        }

        const checkbox = event.currentTarget.querySelector('[data-selection-target="checkbox"]');

        if (!checkbox) {
            return;
        }

        event.preventDefault();

        checkbox.checked = !checkbox.checked;

        this.refresh();
    }

    // Uncheck the items hidden by a filter, so the actions never apply to
    // items that the user doesn't see (a hidden checked input would still be
    // submitted with the form).
    uncheckHidden () {
        this.checkboxTargets.forEach((checkbox) => {
            if (this.isHidden(checkbox)) {
                checkbox.checked = false;
            }
        });

        this.refresh();
    }

    clear () {
        this.checkboxTargets.forEach((checkbox) => {
            checkbox.checked = false;
        });

        this.refresh();
    }

    visibleCheckboxes () {
        return this.checkboxTargets.filter((checkbox) => !this.isHidden(checkbox));
    }

    isHidden (checkbox) {
        return checkbox.closest('[hidden]') !== null;
    }
}
