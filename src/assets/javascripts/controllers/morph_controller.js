import { Controller } from '@hotwired/stimulus';

// Protect the client-side state of an element from the page refreshes rendered
// with morphing: the server doesn't know this state and would reset it.
export default class extends Controller {
    static values = { preserve: String };

    // Keep the attributes listed in the "preserve" value, separated by spaces.
    preserveAttributes (event) {
        // The event bubbles, so it is also dispatched for the attributes of the
        // children of the element.
        if (event.target !== this.element) {
            return;
        }

        if (this.preserveValue.split(' ').includes(event.detail.attributeName)) {
            event.preventDefault();
        }
    }

    // Keep the value of the input being typed in: morphing overwrites it with
    // the one rendered by the server, and the user keeps typing while a visit is
    // in flight, so the characters typed meanwhile would be lost.
    preserveTypedValue (event) {
        if (event.detail.attributeName !== 'value') {
            return;
        }

        if (event.target === document.activeElement) {
            event.preventDefault();
        }
    }
}
