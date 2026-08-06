import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

// Delay before submitting the "query" search, so we don't submit the form on
// each keystroke.
const SEARCH_DELAY = 500;

export default class extends Controller {
    static targets = [
        'formFilters',
        'timeline',
        'at',
        'days',
        'daysInput',
        'atInput',
        'source',
        'sourceInput',
        'status',
        'statusInput',
    ];

    disconnect () {
        clearTimeout(this.searchTimeout);
    }

    selectAt (event) {
        const button = event.currentTarget;

        if (button.getAttribute('aria-pressed') === 'true') {
            return;
        }

        this.atInputTarget.value = button.value;
        this.press(this.atTargets, button);

        this.submit();
    }

    selectDays (event) {
        const button = event.currentTarget;

        if (button.getAttribute('aria-pressed') === 'true') {
            return;
        }

        this.daysInputTarget.value = button.value;
        this.press(this.daysTargets, button);

        this.submit();
    }

    selectSource (event) {
        const button = event.currentTarget;

        // Contrary to the other types of buttons, the sources are toggle buttons:
        // clicking the selected one unselects it.
        const willBeSelected = button.getAttribute('aria-pressed') !== 'true';

        this.sourceInputTarget.value = willBeSelected ? button.value : '';
        this.press(this.sourceTargets, willBeSelected ? button : null);

        this.submit();
    }

    selectStatus (event) {
        const button = event.currentTarget;

        if (button.getAttribute('aria-pressed') === 'true') {
            return;
        }

        this.statusInputTarget.value = button.value;
        this.press(this.statusTargets, button);

        this.submit();
    }

    searchQuery () {
        clearTimeout(this.searchTimeout);

        this.searchTimeout = setTimeout(() => this.submit(), SEARCH_DELAY);
    }

    press (buttons, pressedButton) {
        buttons.forEach((button) => {
            button.setAttribute('aria-pressed', button === pressedButton ? 'true' : 'false');
        });
    }

    submit () {
        clearTimeout(this.searchTimeout);

        // The form is a GET form, so submitting it is equivalent to visiting
        // its URL. The "replace" action makes Turbo render the visit with
        // morphing (cf. the meta tags in the show view), preserving the focus
        // and the scroll positions.
        const form = this.formFiltersTarget;
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();

        // Dim the timeline while the visit is in flight. The morph removes the
        // attribute once the new page is rendered (the server never renders it).
        this.timelineTarget.setAttribute('aria-busy', 'true');

        Turbo.visit(url.toString(), { action: 'replace' });
    }

    submitForm (event) {
        // The form is never submitted by the browser: submit() handles the
        // visit itself so it can be rendered with morphing.
        event.preventDefault();

        this.submit();
    }
};
