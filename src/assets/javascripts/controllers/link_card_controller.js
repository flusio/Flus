import { Controller } from 'stimulus';

import _ from 'js/l10n.js';

export default class extends Controller {
    static get targets () {
        return ['bookmarkForm'];
    }

    toggleBookmarked (event) {
        event.preventDefault();

        const isBookmarked = this.data.get('bookmarked') === 'true';
        if (isBookmarked) {
            this.unbookmark();
        } else {
            this.bookmark();
        }
    }

    bookmark () {
        const action = this.data.get('bookmark-action');
        const card = this.element;
        const form = this.bookmarkFormTarget;
        const button = form.querySelector('button');
        const icon = form.querySelector('.icon--bookmark');

        fetch(action, {
            method: 'post',
            body: new FormData(form),
        }).then(() => {
            this.data.set('bookmarked', 'true');
        });

        card.classList.remove('card--transparent');
        icon.classList.add('icon--solid');
        button.title = _('Remove from bookmarks');
        button.setAttribute('aria-label', _('Remove from bookmarks'));
    }

    unbookmark () {
        const action = this.data.get('unbookmark-action');
        const card = this.element;
        const form = this.bookmarkFormTarget;
        const button = form.querySelector('button');
        const icon = form.querySelector('.icon--bookmark');

        fetch(action, {
            method: 'post',
            body: new FormData(form),
        }).then(() => {
            this.data.set('bookmarked', 'false');
        });

        card.classList.add('card--transparent');
        icon.classList.remove('icon--solid');
        button.title = _('Add to bookmarks');
        button.setAttribute('aria-label', _('Add to bookmarks'));
    }
};
