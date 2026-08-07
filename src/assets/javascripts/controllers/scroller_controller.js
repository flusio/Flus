import { Controller } from '@hotwired/stimulus';

// Handle a strip of items that can be scrolled: it shows shadows on the edges
// that can be scrolled, it moves the focus with the keyboard, and it can be
// scrolled with previous/next buttons.
//
// The strip is scrolled horizontally by default. Set the "vertical" value to
// scroll it vertically.
export default class extends Controller {
    static targets = ['viewport', 'strip', 'item', 'previous', 'next'];

    static values = { vertical: Boolean };

    connect () {
        this.scrollToSelectedItem();
        this.refresh();
    }

    // Take the measurements again, for a strip which was hidden when the
    // controller connected (e.g. inside a collapsed <details>): its sizes were
    // all zero, so the strip was not scrolled to the selected item and the
    // navigation buttons were left disabled.
    reveal () {
        this.scrollToSelectedItem();
        this.refresh();
    }

    // Show the shadows on the edges of the viewport (and enable the
    // navigation buttons) if the strip can be scrolled in that direction.
    refresh () {
        if (!this.hasStripTarget) {
            return;
        }

        if (this.verticalValue) {
            this.refreshVertical();
        } else {
            this.refreshHorizontal();
        }
    }

    refreshHorizontal () {
        const strip = this.stripTarget;
        const maxScrollLeft = strip.scrollWidth - strip.clientWidth;

        // A tolerance of 1px accounts for the subpixel scroll positions.
        const canScrollLeft = strip.scrollLeft > 1;
        const canScrollRight = strip.scrollLeft < maxScrollLeft - 1;

        this.viewportTarget.classList.toggle('scroller--overflow-left', canScrollLeft);
        this.viewportTarget.classList.toggle('scroller--overflow-right', canScrollRight);

        this.refreshButtons(canScrollLeft, canScrollRight);
    }

    refreshVertical () {
        const strip = this.stripTarget;
        const maxScrollTop = strip.scrollHeight - strip.clientHeight;

        const canScrollUp = strip.scrollTop > 1;
        const canScrollDown = strip.scrollTop < maxScrollTop - 1;

        this.viewportTarget.classList.toggle('scroller--overflow-top', canScrollUp);
        this.viewportTarget.classList.toggle('scroller--overflow-bottom', canScrollDown);

        this.refreshButtons(canScrollUp, canScrollDown);
    }

    refreshButtons (canScrollToPrevious, canScrollToNext) {
        if (this.hasPreviousTarget) {
            this.previousTarget.disabled = !canScrollToPrevious;
        }

        if (this.hasNextTarget) {
            this.nextTarget.disabled = !canScrollToNext;
        }
    }

    previous () {
        this.scrollByPage(-1);
    }

    next () {
        this.scrollByPage(1);
    }

    scrollByPage (direction) {
        const strip = this.stripTarget;

        // Scroll by almost a full page, so an item stays visible from a page
        // to the next one.
        if (this.verticalValue) {
            strip.scrollBy({ top: direction * 0.8 * strip.clientHeight });
        } else {
            strip.scrollBy({ left: direction * 0.8 * strip.clientWidth });
        }
    }

    // Move the focus in the strip with the keyboard (roving tabindex): only
    // the focused item is tabbable, and moving the focus does NOT select the
    // item. Selection stays explicit, with Enter, Space or a click.
    navigate (event) {
        const currentIndex = this.itemTargets.indexOf(event.target);

        if (currentIndex < 0) {
            return;
        }

        const previousKey = this.verticalValue ? 'ArrowUp' : 'ArrowLeft';
        const nextKey = this.verticalValue ? 'ArrowDown' : 'ArrowRight';

        let newIndex;

        if (event.key === nextKey) {
            newIndex = currentIndex + 1;
        } else if (event.key === previousKey) {
            newIndex = currentIndex - 1;
        } else if (event.key === 'Home') {
            newIndex = 0;
        } else if (event.key === 'End') {
            newIndex = this.itemTargets.length - 1;
        } else {
            return;
        }

        event.preventDefault();

        newIndex = Math.max(0, Math.min(newIndex, this.itemTargets.length - 1));
        const newItem = this.itemTargets[newIndex];

        this.itemTargets.forEach((item) => {
            item.setAttribute('tabindex', item === newItem ? '0' : '-1');
        });

        newItem.focus();
    }

    scrollToSelectedItem () {
        if (!this.hasStripTarget) {
            return;
        }

        const strip = this.stripTarget;
        const selectedItem = strip.querySelector('[aria-pressed="true"]');

        if (!selectedItem) {
            return;
        }

        // The position is computed from the bounding rects (and not from
        // offsetLeft/offsetTop) as an item may be wrapped in a positioned
        // element, which would then be its offset parent instead of the strip.
        const stripRect = strip.getBoundingClientRect();
        const itemRect = selectedItem.getBoundingClientRect();

        if (this.verticalValue) {
            const itemPosition = itemRect.top - stripRect.top + strip.scrollTop;

            strip.scrollTo({
                top: itemPosition - (strip.clientHeight - itemRect.height) / 2,
                behavior: 'instant',
            });
        } else {
            const itemPosition = itemRect.left - stripRect.left + strip.scrollLeft;

            strip.scrollTo({
                left: itemPosition - (strip.clientWidth - itemRect.width) / 2,
                behavior: 'instant',
            });
        }
    }
};
