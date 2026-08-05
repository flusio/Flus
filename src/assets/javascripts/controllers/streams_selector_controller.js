import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['search', 'option', 'empty'];
    }

    filter () {
        const query = this.normalize(this.searchTarget.value);

        const matchingOptions = this.optionTargets.filter((option) => {
            // Note the options are only hidden: a checked stream that is
            // filtered out must still be submitted with the form.
            const matching = this.normalize(option.dataset.name).includes(query);
            option.hidden = !matching;
            return matching;
        });

        this.emptyTarget.hidden = matchingOptions.length > 0;

        // The height of the options changed, so the scroller must recalculate
        // its shadows.
        this.dispatch('filtered');
    }

    // Return a comparable version of the value, so searching for "eco" matches
    // a stream named "Écologie".
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
