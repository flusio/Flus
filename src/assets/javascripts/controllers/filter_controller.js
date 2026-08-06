import { Controller } from '@hotwired/stimulus';

// Filter a list of items on the client side.
//
// The items are matched against a search field (on their `data-name`) and
// against a set of flags: each `flag` target is a checkbox declaring a
// `data-flag` name, and the items declare the flags they carry in their
// `data-flags` attribute (separated by spaces). An item matches only if it
// carries all the checked flags.
//
// All the targets but `item` are optional, so a list can be filtered by a
// search field only, by flags only, or by both.
export default class extends Controller {
    static targets = ['search', 'flag', 'item', 'empty'];

    connect () {
        this.filter();
    }

    filter () {
        const query = this.hasSearchTarget ? this.normalize(this.searchTarget.value) : '';
        const flags = this.flagTargets
            .filter((flag) => flag.checked)
            .map((flag) => flag.dataset.flag);

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

        this.dispatch('filtered');
    }

    match (item, query, flags) {
        if (!this.normalize(item.dataset.name).includes(query)) {
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
