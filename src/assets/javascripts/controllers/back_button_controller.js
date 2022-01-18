import { Controller } from '@hotwired/stimulus';

import _ from 'js/l10n.js';
import icon from 'js/icon.js';

export default class extends Controller {
    static values = {
        pageTitle: String,
        clearHistory: Boolean,
    }

    static targets = ['button']

    initialize () {
        // Stack the current path at the top of the history
        const currentPath = window.location.pathname + window.location.search;
        let backHistory = this.getBackHistory();
        if (this.clearHistoryValue) {
            backHistory = [];
        }
        this.pushPathToHistory(backHistory, currentPath);
        window.localStorage.setItem('back-history', JSON.stringify(backHistory));

        // We look for the path preceding the current path. It must be the
        // second last item of the history (since current path is always last)
        const backItem = backHistory[backHistory.length - 2];
        if (backItem) {
            // If the item exists, we update the back button
            this.buttonTarget.href = backItem.path;
            this.buttonTarget.innerHTML = `${icon('back')} ${_('Back')} (${backItem.title})`;
        } else {
            // Else, there's nowhere to go so we hide the navigation
            this.element.style.display = 'none';
        }
    }

    handleClick (event) {
        const backHistory = this.getBackHistory();
        const targetPath = event.target.pathname + event.target.search;

        // We update the history to pop elements until the last item
        // corresponds to the path we’re targeting
        this.popHistoryUntilPath(backHistory, targetPath);

        window.localStorage.setItem('back-history', JSON.stringify(backHistory));
    }

    handlePopstate (event) {
        if (event.state) {
            const backHistory = this.getBackHistory();
            const currentPath = window.location.pathname + window.location.search;

            if (backHistory.some((item) => item.path === currentPath)) {
                this.popHistoryUntilPath(backHistory, currentPath);
                window.localStorage.setItem('back-history', JSON.stringify(backHistory));
            } else {
                // If the path is not in our history, it's probably because the
                // user went back then forward with the browser buttons. In
                // this case, we have nothing to do (the path will be added
                // normally by the initialize method).
            }
        }
    }

    popHistoryUntilPath (backHistory, path) {
        while (backHistory.length > 0) {
            const item = backHistory.pop();
            if (item.path === path) {
                backHistory.push(item);
                break;
            }
        }
    }

    pushPathToHistory (backHistory, path) {
        if (
            backHistory.length === 0 ||
            backHistory[backHistory.length - 1].path !== path
        ) {
            backHistory.push({
                title: this.pageTitleValue,
                path: path,
            });
        }
    }

    getBackHistory () {
        const backHistory = window.localStorage.getItem('back-history');
        if (backHistory) {
            return JSON.parse(backHistory);
        } else {
            return [];
        }
    }
};
