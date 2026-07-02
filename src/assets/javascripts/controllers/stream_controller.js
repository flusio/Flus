import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['formFilters', 'source', 'sourceInput', 'status', 'statusInput'];

    selectSource (event) {
        const button = event.currentTarget;

        // The sources are toggle buttons: clicking the selected one unselects it.
        const willBeSelected = button.getAttribute('aria-pressed') !== 'true';

        this.sourceInputTarget.value = willBeSelected ? button.value : '';
        this.press(this.sourceTargets, willBeSelected ? button : null);

        this.submit();
    }

    selectStatus (event) {
        const button = event.currentTarget;

        // Contrary to the sources, a status is always selected: clicking the
        // selected one does nothing.
        if (button.getAttribute('aria-pressed') === 'true') {
            return;
        }

        this.statusInputTarget.value = button.value;
        this.press(this.statusTargets, button);

        this.submit();
    }

    press (buttons, pressedButton) {
        buttons.forEach((button) => {
            button.setAttribute('aria-pressed', button === pressedButton ? 'true' : 'false');
        });
    }

    submit () {
        // requestSubmit() (and not submit()) so Turbo intercepts the
        // submission and only renders the timeline frame.
        this.formFiltersTarget.requestSubmit();
    }
};
