import { Controller } from '@hotwired/stimulus';

// Move the focus after a page refresh.
//
// The refresh can remove the element having the focus (e.g. the button of a
// form whose item moves elsewhere in the page). The focus goes to the next
// `item` target of the list (or to the previous one if it was the last), so
// several forms of a same list can be submitted in a row without leaving the
// keyboard.
//
// The `fallback` target takes the focus when the list has no item left, or when
// the item to focus cannot take it: it must be an element which is always
// reachable, whatever the state of the list.
//
// The element to focus is chosen when the form is submitted, while the page is
// still intact, then focused on `turbo:render`, once the new page is rendered.
//
// An item can also be designated by the server as the `autofocus` target: it
// takes the focus on the next render, so an item that didn't exist yet at
// submission time can be focused.
export default class extends Controller {
    static targets = ['item', 'fallback', 'autofocus'];

    remember (event) {
        this.elementToFocus = null;

        const element = this.itemTargets.find((item) => item.contains(event.target));

        if (!element) {
            return;
        }

        const fallback = this.hasFallbackTarget ? this.fallbackTarget : null;
        this.elementToFocus = this.siblingToFocus(element) ?? fallback;
    }

    // Remember the item itself instead of its sibling, for the actions which
    // keep it in the list (e.g. a modal opened from the item and submitted).
    rememberSelf (event) {
        this.elementToFocus = this.itemTargets.find((item) => item.contains(event.target)) ?? null;
    }

    restore () {
        const autofocusElement = this.hasAutofocusTarget ? this.autofocusTarget : null;
        const element = this.elementToFocus ?? autofocusElement;
        this.elementToFocus = null;

        if (!element) {
            return;
        }

        element.focus();

        if (document.activeElement !== element && this.hasFallbackTarget) {
            this.fallbackTarget.focus();
        }
    }

    // Return the closest item which can take the focus: the next one, or the
    // previous one when the element is the last of the list.
    siblingToFocus (element) {
        const items = this.itemTargets.filter((item) => !item.hidden || item === element);
        const index = items.indexOf(element);

        return items[index + 1] ?? items[index - 1];
    }
}
