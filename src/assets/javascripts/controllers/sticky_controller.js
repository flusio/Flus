import { Controller } from '@hotwired/stimulus';

// Reflect the stuck state of a sticky element in its `data-stuck` attribute,
// so the CSS can style the element differently while it actually sticks.
//
// The element must be positioned with `top: -1px` so while it sticks, its top
// pixel is out of the viewport, which the IntersectionObserver detects.
export default class extends Controller {
    connect () {
        this.observer = new IntersectionObserver(([entry]) => {
            const stuck = (
                entry.intersectionRatio < 1 &&
                entry.boundingClientRect.top <= entry.rootBounds.top
            );

            this.element.toggleAttribute('data-stuck', stuck);
        }, { threshold: [1] });

        this.observer.observe(this.element);
    }

    disconnect () {
        this.observer.disconnect();
    }
}
