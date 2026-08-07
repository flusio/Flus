import { Controller } from '@hotwired/stimulus';

// Filter a list of items on the client side.
//
// The items are matched against a search field: each item declares in its
// `data-search` attribute the JSON list of the terms on which it can be
// searched. The terms are matched one by one, so a query cannot match across
// two of them.
//
// The items are also matched against a set of flags. A `flag` target names the
// flag it selects in its `value`: a checkbox, a toggle button, or a select
// whose options are the flags of a same filter (an empty value selecting no
// flag). The items declare the flags they carry in their `data-flags` attribute
// (separated by spaces). An item matches only if it carries all the selected
// flags.
//
// The toggle buttons of a same filter are exclusive: they must be wrapped in a
// container marked with `role="group"`, which delimits the buttons to unpress
// when one of them is selected.
//
// All the targets but `item` are optional, so a list can be filtered by a
// search field only, by flags only, or by both.
//
// The `count` target receives the number of matching items while a filter is
// active. It is meant to be placed in front of the total number of items, which
// the server renders.
export default class extends Controller {
    static targets = ['search', 'flag', 'item', 'empty', 'count'];

    connect () {
        this.filter();
    }

    filter () {
        const query = this.hasSearchTarget ? this.normalize(this.searchTarget.value) : '';
        const flags = this.flagTargets
            .map((flag) => this.flagValue(flag))
            .filter((flag) => flag);

        const matchingItems = this.itemTargets.filter((item) => {
            // Note the items are only hidden: an item that is filtered out may
            // still contain a checked input which must be submitted with the
            // form.
            const matching = this.match(item, query, flags);
            item.hidden = !matching;
            return matching;
        });

        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = matchingItems.length > 0;
        }

        if (this.hasCountTarget) {
            // The total must be rendered by the server, next to this
            // target: only the number of matching items is displayed in
            // front of it, and only while a filter is active.
            const isFiltering = query !== '' || flags.length > 0;
            this.countTarget.textContent = isFiltering ? `${matchingItems.length} /` : '';
        }

        this.dispatch('filtered');
    }

    // Select a toggle button and unselect the other buttons of its group.
    selectFlag (event) {
        const button = event.currentTarget;

        if (button.getAttribute('aria-pressed') === 'true') {
            return;
        }

        const group = button.closest('[role="group"]');
        group.querySelectorAll('[aria-pressed]').forEach((other) => {
            other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
        });

        this.filter();
    }

    // Return the flag carried by a target. They all declare it as their
    // `value`, but a checkbox only carries it while it is checked, and a toggle
    // button while it is pressed.
    flagValue (flag) {
        if (flag.type === 'checkbox') {
            return flag.checked ? flag.value : '';
        }

        if (flag.hasAttribute('aria-pressed')) {
            return flag.getAttribute('aria-pressed') === 'true' ? flag.value : '';
        }

        return flag.value;
    }

    match (item, query, flags) {
        const searchableTerms = JSON.parse(item.dataset.search);
        const matchingQuery = searchableTerms.some((term) => {
            return this.normalize(term).includes(query);
        });

        if (!matchingQuery) {
            return false;
        }

        const itemFlags = (item.dataset.flags ?? '').split(' ');
        return flags.every((flag) => itemFlags.includes(flag));
    }

    // Return a comparable version of the value, so searching for "eco" matches
    // a attribute titled "Écologie".
    //
    // The NFD normalization splits the accented characters into a base letter
    // followed by a combining mark ("é" becomes "e" + U+0301). The Diacritic
    // Unicode property matches these marks, which are then removed.
    //
    // The characters that have no canonical decomposition are left alone, so
    // "ø" or "ß" still have to be typed as is.
    normalize (value) {
        return value.normalize('NFD').replace(/\p{Diacritic}/gu, '').trim().toLowerCase();
    }
}
