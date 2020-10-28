import { Controller } from 'stimulus';

export default class extends Controller {
    static get targets () {
        return ['mask'];
    }

    bookmark (event) {
        event.preventDefault();

        const card = this.element;
        const form = event.target;
        const mask = this.maskTarget;

        fetch(form.action, {
            method: 'post',
            body: new FormData(form),
        }).then(() => {
            this.data.set('bookmarked', 'true');
        });

        card.classList.remove('card--shadow');
        mask.style.opacity = 0;
        setTimeout(() => { mask.style.display = 'none'; }, 500);
    }

    unbookmark (event) {
        event.preventDefault();

        const card = this.element;
        const form = event.target;
        const mask = this.maskTarget;

        fetch(form.action, {
            method: 'post',
            body: new FormData(form),
        }).then(() => {
            this.data.set('bookmarked', 'false');
        });

        card.classList.add('card--shadow');
        mask.style.display = 'flex';
        setTimeout(() => { mask.style.opacity = 1; }, 100);
    }
};
