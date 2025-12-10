import { Controller } from '@hotwired/stimulus';
import { length } from 'stringz';
import regexSupplant from 'twitter-text/dist/lib/regexSupplant';
import validDomain from 'twitter-text/dist/regexp/validDomain';
import validPortNumber from 'twitter-text/dist/regexp/validPortNumber';
import validUrlPath from 'twitter-text/dist/regexp/validUrlPath';
import validUrlPrecedingChars from 'twitter-text/dist/regexp/validUrlPrecedingChars';
import validUrlQueryChars from 'twitter-text/dist/regexp/validUrlQueryChars';
import validUrlQueryEndingChars from 'twitter-text/dist/regexp/validUrlQueryEndingChars';

import icon from '../icon.js';
import _ from '../l10n.js';

// Code from Mastodon (AGPL)
// @see https://github.com/mastodon/mastodon/blob/main/app/javascript/mastodon/features/compose/util/url_regex.js
const urlRegex = regexSupplant(
    '('                                                        + // $1 URL
    '(#{validUrlPrecedingChars})'                              + // $2
    '(https?:\\/\\/)'                                          + // $3 Protocol
    '(#{validDomain})'                                         + // $4 Domain(s)
    '(?::(#{validPortNumber}))?'                               + // $5 Port number (optional)
    '(\\/#{validUrlPath}*)?'                                   + // $6 URL Path
    '(\\?#{validUrlQueryChars}*#{validUrlQueryEndingChars})?'  + // $7 Query String
    ')',
    {
        validUrlPrecedingChars,
        validDomain,
        validPortNumber,
        validUrlPath,
        validUrlQueryChars,
        validUrlQueryEndingChars,
    },
    'gi',
);

const urlPlaceholder = '$2xxxxxxxxxxxxxxxxxxxxxxx';

export default class extends Controller {
    static targets = ['source', 'counter']

    static values = {
        max: Number,
    }

    connect () {
        this.updateCounter();
    }

    updateCounter () {
        const count = this.countCharacters(this.sourceTarget.value);

        let label = _('{{count}} characters out of a maximum of {{max}}');
        label = label.replace('{{count}}', count);
        label = label.replace('{{max}}', this.maxValue);
        this.counterTarget.ariaLabel = label;

        if (count > this.maxValue) {
            this.counterTarget.innerHTML = `${count}&nbsp;/&nbsp;${this.maxValue}&nbsp;${icon('error')}`;

            this.counterTarget.classList.add('counter--over');

            this.sourceTarget.setCustomValidity(_('The post is too long.'));
            this.sourceTarget.ariaInvalid = 'true';
        } else {
            this.counterTarget.innerHTML = `${count}&nbsp;/&nbsp;${this.maxValue}`;

            this.counterTarget.classList.remove('counter--over');

            this.sourceTarget.setCustomValidity('');
            this.sourceTarget.ariaInvalid = null;
        }
    }

    countCharacters(text) {
        // Code from Mastodon (AGPL)
        // @see https://github.com/mastodon/mastodon/blob/main/app/javascript/mastodon/features/compose/util/counter.js
        let countableText = text;
        countableText = countableText.replace(urlRegex, urlPlaceholder);
        countableText = countableText.replace(/(^|[^/\w])@(([a-z0-9_]+)@[a-z0-9.-]+[a-z0-9]+)/ig, '$1@$3');

        return length(countableText);
    }
}
