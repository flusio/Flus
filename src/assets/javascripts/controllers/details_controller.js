import { Controller } from '@hotwired/stimulus';

const DURATION = 400;

// Animate the opening and the closing of a <details> element, which the browser
// otherwise toggles instantly.
//
// The height of the element is animated between its collapsed and its expanded
// heights, and the `open` attribute is only removed once the closing animation
// is over.
//
// The state can be remembered from a visit to another by declaring a storage key.
export default class extends Controller {
    static values = {
        // While a closing animation is in flight, the element is still open:
        // the expected state is tracked in this value instead of being read
        // from the element.
        collapsed: Boolean,

        // Optional: when it is set, the state is remembered under this key.
        storageKey: String,
    };

    connect () {
        this.restore();

        this.collapsedValue = !this.element.open;
    }

    toggle (event) {
        this.collapsedValue = !this.collapsedValue;

        this.store();

        // Let the browser toggle the element instantly when the animations are
        // not welcome.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            if (!this.collapsedValue) {
                // The browser only opens the element after this handler: the
                // announcement is delayed by a frame so the content is
                // measurable when it is received.
                requestAnimationFrame(() => this.dispatch('opened'));
            }

            return;
        }

        event.preventDefault();

        if (this.collapsedValue) {
            this.close();
        } else {
            this.open();
        }
    }

    open () {
        const startHeight = this.currentHeight();

        this.cancelAnimation();
        this.element.open = true;

        // The content is only announced once the element reached its expanded
        // height: it may have been rendered while collapsed, with all its sizes
        // to zero, and measuring it mid-animation would be just as wrong. A
        // cancelled animation fires "cancel" instead, so an opening interrupted
        // by a closing announces nothing.
        this.animateHeight(startHeight, this.element.offsetHeight)
            .addEventListener('finish', () => {
                this.dispatch('opened');
            });
    }

    close () {
        const startHeight = this.currentHeight();

        this.cancelAnimation();

        // Measure the collapsed height, then reopen the element: both happen in
        // the same frame, so nothing is painted in between.
        this.element.open = false;
        const endHeight = this.element.offsetHeight;
        this.element.open = true;

        this.animateHeight(startHeight, endHeight).addEventListener('finish', () => {
            this.element.open = false;
        });
    }

    get storageNamespace () {
        return `details:${this.storageKeyValue}`;
    }

    // Apply the remembered state, if any. The element is opened or closed
    // without animating: it must be rendered in its final state.
    restore () {
        if (!this.hasStorageKeyValue) {
            return;
        }

        const storedState = window.localStorage.getItem(this.storageNamespace);

        if (storedState === 'collapsed' || storedState === 'expanded') {
            this.element.open = storedState === 'expanded';
        }
    }

    store () {
        if (!this.hasStorageKeyValue) {
            return;
        }

        window.localStorage.setItem(
            this.storageNamespace,
            this.collapsedValue ? 'collapsed' : 'expanded',
        );
    }

    // Animate the height of the element. Any animation in flight must be
    // cancelled first, as it would constrain the measured heights.
    animateHeight (fromHeight, toHeight) {
        this.element.style.overflow = 'hidden';

        this.animation = this.element.animate(
            { height: [`${fromHeight}px`, `${toHeight}px`] },
            { duration: DURATION, easing: 'ease-in-out' },
        );

        this.animation.addEventListener('finish', () => {
            this.element.style.overflow = '';
            this.animation = null;
        });

        return this.animation;
    }

    cancelAnimation () {
        this.animation?.cancel();
        this.animation = null;
    }

    // The height currently rendered, which can be an intermediate height of an
    // animation in flight.
    currentHeight () {
        return this.element.getBoundingClientRect().height;
    }
}
