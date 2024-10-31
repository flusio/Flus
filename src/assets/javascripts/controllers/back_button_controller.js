import { Controller } from '@hotwired/stimulus';

import _ from '../l10n.js';
import icon from '../icon.js';

export default class extends Controller {
    static values = {
        title: String,
        reset: Boolean,
        track: Boolean,
    };

    static targets = ['button'];

    initialize () {
        let backHistory = this.getBackHistory();

        if (this.resetValue) {
            backHistory = [];
        }

        if (
            this.trackValue &&
            // This URL is automatically redirected to /collections/:id.
            // Unfortunately, because how Turbo works, both URLs call this
            // initialize() method and add them to the history. I don't have a
            // simple and clean solution on the backend-side to solve this
            // issue, so I use this little hack to not track the /about/new URL.
            window.location.pathname !== '/about/new'
        ) {
            // Stack the current path at the top of the history only if we’re
            // asked to track the current page
            const currentPath = window.location.pathname + window.location.search;
            this.pushPathToHistory(backHistory, currentPath);
        }

        window.localStorage.setItem('back-history', JSON.stringify(backHistory));

        // We look for the path preceding the current path. It must be the
        // second last item of the history (if current path is tracked), or the
        // last item (if current path is not tracked).
        const previousItemIndex = backHistory.length - (this.trackValue ? 2 : 1);
        const backItem = backHistory[previousItemIndex];
        if (backItem) {
            // If the item exists, we update the back button
            this.buttonTarget.href = backItem.path;
            this.buttonTarget.innerHTML = `${icon('back')} ${_('Back')} (${backItem.title})`;
        } else {
            // Else, there's nowhere to go so we remove the navigation
            this.element.remove();
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
                title: this.titleValue,
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
