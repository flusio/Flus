import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect () {
        const colorScheme = document.documentElement.dataset.colorScheme;
        if (colorScheme !== 'system') {
            return;
        }

        if (!window.matchMedia) {
            document.documentElement.dataset.colorScheme = 'light';
            return;
        }

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const prefersDarkColorScheme = mediaQuery.matches;

        if (prefersDarkColorScheme) {
            document.documentElement.dataset.colorScheme = 'dark';
        } else {
            document.documentElement.dataset.colorScheme = 'light';
        }

        mediaQuery.addEventListener('change', (event) => {
            document.documentElement.dataset.colorScheme = event.matches ? 'dark' : 'light';
        });
    }
}
