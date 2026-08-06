import { Controller } from '@hotwired/stimulus';

const DURATION = 400;

// Animate the opening and the closing of a <details> element, which the browser
// otherwise toggles instantly.
//
// The height of the element is animated between its collapsed and its expanded
// heights, and the `open` attribute is only removed once the closing animation
// is over.
export default class extends Controller {
    // While a closing animation is in flight, the element is still open: the
    // expected state is tracked in this value instead of being read from the
    // element.
    static values = {
        collapsed: Boolean,
    };

    connect () {
        this.collapsedValue = !this.element.open;
    }

    toggle (event) {
        this.collapsedValue = !this.collapsedValue;

        // Let the browser toggle the element instantly when the animations are
        // not welcome.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
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

        this.animateHeight(startHeight, this.element.offsetHeight);
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
