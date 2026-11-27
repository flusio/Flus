import { Controller } from '@hotwired/stimulus';

// Click an element when pressing its keyboard shortcut.
//
// The shortcuts are declared on the `trigger` targets with the `aria-keyshortcuts`
// attribute. The attribute can list several shortcuts separated by spaces.

const MODIFIERS = ['control', 'alt', 'shift', 'meta'];

export default class extends Controller {
    static targets = ['trigger'];

    handle (event) {
        // The keys pressed while composing text (e.g. with an input method
        // editor) belong to the composition.
        if (event.isComposing) {
            return;
        }

        const shortcut = shortcutFromEvent(event);

        const trigger = this.triggerTargets.find((target) => {
            return shortcutsFromElement(target).includes(shortcut);
        });

        if (trigger) {
            event.preventDefault();
            trigger.click();
        }
    }
};

// Return the shortcut of a keydown event, normalized as in `normalizeShortcut()`.
function shortcutFromEvent (event) {
    const keys = [];

    if (event.ctrlKey) {
        keys.push('control');
    }

    if (event.altKey) {
        keys.push('alt');
    }

    if (event.shiftKey) {
        keys.push('shift');
    }

    if (event.metaKey) {
        keys.push('meta');
    }

    keys.push(event.key.toLowerCase());

    return keys.join('+');
}

// Return the normalized shortcuts declared by the `aria-keyshortcuts`
// attribute of the element.
function shortcutsFromElement (element) {
    const attribute = element.getAttribute('aria-keyshortcuts') ?? '';

    return attribute
        .split(' ')
        .filter((shortcut) => shortcut !== '')
        .map(normalizeShortcut);
}

// Lowercase the shortcut and put its modifiers in a constant order, so
// `Shift+Control+A` and `Control+Shift+A` are the same shortcut.
function normalizeShortcut (shortcut) {
    const keys = shortcut.toLowerCase().split('+');
    const key = keys.pop();
    const modifiers = MODIFIERS.filter((modifier) => keys.includes(modifier));

    return [...modifiers, key].join('+');
}
