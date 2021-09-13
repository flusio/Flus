import { Controller } from 'stimulus';

const FOCUSABLE_ELEMENTS = [
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled]):not([tabindex="-1"])',
    'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
    'select:not([disabled]):not([tabindex="-1"])',
    'textarea:not([disabled]):not([tabindex="-1"])',
    '[tabindex]:not([tabindex="-1"])',
];

// Credits to:
// - JoliCode: https://jolicode.com/blog/une-fenetre-modale-accessible
// - Sabe: https://sabe.io/tutorials/how-to-create-modal-popup-box
// - Myself: https://github.com/lessy-community/lessy/blob/master/client/src/components/Ly/LyModal.vue
export default class extends Controller {
    static get targets () {
        return ['box', 'body', 'content'];
    }

    connect () {
        this.element.addEventListener('open-modal', this.open.bind(this));

        // handle modal shortcuts
        this.element.addEventListener('keydown', this.trapEscape.bind(this));
        this.element.addEventListener('keydown', this.trapFocus.bind(this));

        // set focus elements when the modal is loaded
        document.documentElement.addEventListener('turbo:frame-render', (event) => {
            if (event.target === this.contentTarget) {
                this.setFocus();
                this.boxTarget.scrollIntoView(true);
            }
        });
    }

    setFocus () {
        // We want to get the first and last focusable elements in the modal...
        // ... start by getting the list of potential focusable elements...
        const focusableElements = Array.from(this.boxTarget.querySelectorAll(FOCUSABLE_ELEMENTS));
        // ... there's always at least one focusable element (i.e. the close button)...
        this.firstFocusableElement = focusableElements[0];
        // ... but we don't know what the last focusable element is, and it
        // might be hidden (so the focus will not be given), so we iterate
        // backwards to find the last "real" focusable element.
        for (let index = focusableElements.length - 1; index >= 0; index--) {
            const element = focusableElements[index];
            element.focus();

            if (document.activeElement === element) {
                this.lastFocusableElement = element;
                break;
            }
        }

        // We want to give the focus to the "first" element of the modal which
        // is the close button in the header. But this is not the ideal
        // candidate for the focus because it's not part of the modal body.
        // In consequence, if we have more than one focusable elements (i.e.
        // there's at least one in the body), we give the focus to the second
        // element.
        if (focusableElements.length > 1) {
            focusableElements.shift();
        }

        let hasFocus = false;
        for (const element of focusableElements) {
            element.focus();

            // the element might still not have the focus, if it's hidden for instance
            if (document.activeElement === element) {
                hasFocus = true;
                break;
            }
        };

        if (!hasFocus) {
            // still no focus? Let’s focus the close button then!
            this.firstFocusableElement.focus();
        }
    }

    open (event) {
        const layout = document.getElementById('layout');

        // show the modal and set accessible attributes
        this.element.setAttribute('aria-hidden', false);
        layout.setAttribute('aria-hidden', true);
        document.body.classList.add('modal-opened');

        // load the modal content via turbo-frame
        this.contentTarget.setAttribute('src', event.detail.href);

        // remember the current element to give it the focus back on close
        this.focusBackElement = event.detail.target;
    }

    closeOnMask (event) {
        // This allows to close the modal by clicking outside the box
        if (event.target.id === 'modal') {
            this.close(event);
        }
    }

    close (event) {
        event.preventDefault();

        const layout = document.getElementById('layout');

        // close the modal and set accessible attributes
        this.element.setAttribute('aria-hidden', true);
        layout.setAttribute('aria-hidden', false);
        document.body.classList.remove('modal-opened');

        // unload the turbo-frame with a timeout to wait for the modal close
        // animation
        setTimeout(() => {
            this.contentTarget.setAttribute('src', '');
            this.contentTarget.innerHTML = '<div class="spinner"></div>';
        }, 300);

        // give the focus back to the link/button that opened the modal
        if (this.focusBackElement) {
            this.focusBackElement.focus();
            this.focusBackElement = null;
        }
    }

    trapEscape (event) {
        if (event.key === 'Escape') {
            this.close(event);
        }
    }

    trapFocus (event) {
        if (event.key !== 'Tab') {
            return;
        }

        if (event.target === this.firstFocusableElement && event.shiftKey) {
            event.preventDefault();
            this.lastFocusableElement.focus();
        } else if (event.target === this.lastFocusableElement && !event.shiftKey) {
            event.preventDefault();
            this.firstFocusableElement.focus();
        }
    }
};
