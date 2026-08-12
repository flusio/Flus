import { Controller } from '@hotwired/stimulus';

// Move the focus after a page refresh.
//
// The refresh can remove the item having the focus (e.g. the button of a form
// whose item moves elsewhere in the page), or hide it (e.g. an item edited so
// it no longer matches the filters of the list). The focus stays on the item
// if it is still in the list, or goes to the closest `item` target (the next
// one, or the previous one if it was the last), so several forms of a same
// list can be submitted in a row without leaving the keyboard.
//
// The `fallback` target takes the focus when the list has no item left, or
// when the element to focus cannot take it: it must be an element which is
// always reachable, whatever the state of the list.
//
// The item and its sibling are remembered when the action starts (a submit,
// or a click opening a modal), while the page is still intact. The one to
// focus is chosen on `turbo:render`, once the new page is rendered and the
// list has been filtered again.
//
// An item can also be designated by the server as the `autofocus` target: it
// takes the focus on the next render, so an item that didn't exist yet at
// submission time can be focused.
export default class extends Controller {
    static targets = ['item', 'fallback', 'autofocus'];

    remember (event) {
        const element = this.itemTargets.find((item) => item.contains(event.target)) ?? null;

        this.rememberedItem = element;
        this.rememberedSibling = element ? this.siblingToFocus(element) : null;
    }

    restore () {
        const fallback = this.hasFallbackTarget ? this.fallbackTarget : null;
        const autofocusElement = this.hasAutofocusTarget ? this.autofocusTarget : null;

        let element = this.rememberedItem;

        if (element && !this.isInList(element)) {
            element = this.rememberedSibling ?? fallback;
        }

        element = element ?? autofocusElement;

        this.rememberedItem = null;
        this.rememberedSibling = null;

        if (!element) {
            return;
        }

        element.focus();

        if (document.activeElement !== element && fallback) {
            fallback.focus();
        }
    }

    // Tell whether the item is still in the list: the refresh may have removed
    // it, or the filters may hide it now.
    isInList (item) {
        return this.itemTargets.includes(item) && !item.hidden;
    }

    // Return the closest item which can take the focus: the next one, or the
    // previous one when the element is the last of the list.
    siblingToFocus (element) {
        const items = this.itemTargets.filter((item) => !item.hidden || item === element);
        const index = items.indexOf(element);

        return items[index + 1] ?? items[index - 1];
    }
}
