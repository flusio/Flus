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
        return ['box', 'body'];
    }

    connect () {
        this.element.addEventListener('open-modal', this.open.bind(this));
        this.element.addEventListener('update-modal', this.update.bind(this));

        // handle modal shortcuts
        this.element.addEventListener('keydown', this.trapEscape.bind(this));
        this.element.addEventListener('keydown', this.trapFocus.bind(this));
    }

    open (event) {
        const layout = document.getElementById('layout');

        // show the modal and set accessible attributes
        this.element.setAttribute('aria-hidden', false);
        layout.setAttribute('aria-hidden', true);
        document.body.classList.add('modal-opened');

        // set the modal content
        this.setContent(event.detail.content);

        // remember the current element to give it the focus back on close
        this.focusBackElement = event.detail.src;
    }

    update (event) {
        const isOpened = this.element.getAttribute('aria-hidden');

        if (isOpened) {
            this.setContent(event.detail.content);
            this.focusBackElement = event.detail.src;
        }
    }

    setContent (content) {
        // set the content to the modal body
        if (content) {
            this.bodyTarget.innerHTML = content;
            this.bodyTarget.classList.add('modal__body--has-content');
        } else {
            this.bodyTarget.innerHTML = '<div class="spinner"></div>';
            this.bodyTarget.classList.remove('modal__body--has-content');
        }

        // get first and last focusable elements in the modal. There's always
        // at least one focusable element (i.e. the close button).
        const focusableElements = this.boxTarget.querySelectorAll(FOCUSABLE_ELEMENTS);
        this.firstFocusableElement = focusableElements[0];
        this.lastFocusableElement = focusableElements[focusableElements.length - 1];

        // We want to give the focus to the "first" element of the modal which
        // is the close button in the header. But this is not the ideal
        // candidate for the focus because it's not part of the modal body.
        // In consequence, if we have more than one focusable elements (i.e.
        // there's at least one in the body), we give the focus to the second
        // element.
        if (focusableElements.length > 1) {
            focusableElements[1].focus();
        } else {
            this.firstFocusableElement.focus();
        }
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

        // remove the content with a timeout to wait for the modal close
        // animation
        setTimeout(() => {
            this.setContent(null);
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
