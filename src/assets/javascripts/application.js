import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import AutosubmitController from './controllers/autosubmit_controller.js';
import AutosaveController from './controllers/autosave_controller.js';
import BackButtonController from './controllers/back_button_controller.js';
import CaptionSwitcherController from './controllers/caption_switcher_controller.js';
import CharactersCounterController from './controllers/characters_counter_controller.js';
import CollectionsSelectorController from './controllers/collections_selector_controller.js';
import CopyToClipboardController from './controllers/copy_to_clipboard_controller.js';
import CsrfController from './controllers/csrf_controller.js';
import FormFileController from './controllers/form_file_controller.js';
import GroupSelectorController from './controllers/group_selector_controller.js';
import InputPasswordController from './controllers/input_password_controller.js';
import ItemsController from './controllers/items_controller.js';
import LinkSuggestionController from './controllers/link_suggestion_controller.js';
import ModalController from './controllers/modal_controller.js';
import ModalOpenerController from './controllers/modal_opener_controller.js';
import NewsRefresherController from './controllers/news_refresher_controller.js';
import PopupController from './controllers/popup_controller.js';
import SidenavController from './controllers/sidenav_controller.js';
import TextEditorController from './controllers/text_editor_controller.js';
import ZindexInverserController from './controllers/zindex_inverser_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

const application = Application.start();
application.register('autosubmit', AutosubmitController);
application.register('autosave', AutosaveController);
application.register('back-button', BackButtonController);
application.register('caption-switcher', CaptionSwitcherController);
application.register('characters-counter', CharactersCounterController);
application.register('collections-selector', CollectionsSelectorController);
application.register('copy-to-clipboard', CopyToClipboardController);
application.register('csrf', CsrfController);
application.register('form-file', FormFileController);
application.register('group-selector', GroupSelectorController);
application.register('input-password', InputPasswordController);
application.register('items', ItemsController);
application.register('link-suggestion', LinkSuggestionController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('news-refresher', NewsRefresherController);
application.register('popup', PopupController);
application.register('sidenav', SidenavController);
application.register('text-editor', TextEditorController);
application.register('zindex-inverser', ZindexInverserController);

function adaptLayoutContentBorderRadius () {
    const layoutContentNode = document.querySelector('.layout__content');
    if (!layoutContentNode) {
        return;
    }

    const bottomPosition = layoutContentNode.offsetTop + layoutContentNode.clientHeight;
    if (bottomPosition >= document.body.clientHeight) {
        layoutContentNode.classList.add('layout__content--touch-bottom');
    }
}

document.addEventListener('turbo:load', adaptLayoutContentBorderRadius);

// Make sure to visit the response when receiving the `turbo:frame-missing` event.
// This happens most of the time on redirection after submitting a form in a modal.
// Otherwise, "Content missing" would be displayed within the modal.
document.addEventListener('turbo:frame-missing', (event) => {
    event.preventDefault();
    event.detail.visit(event.detail.response);
});

// Allow to disable scroll on form submission.
// Submitting forms with a `data-turbo-preserve-scroll` attribute will keep the
// scroll position at the current position.
let disableScroll = false;

document.addEventListener('turbo:submit-start', (event) => {
    if (event.detail.formSubmission.formElement.hasAttribute('data-turbo-preserve-scroll')) {
        disableScroll = true;
    }
});

document.addEventListener('turbo:before-render', () => {
    if (disableScroll) {
        // As explained on GitHub, `Turbo.navigator.currentVisit.scrolled`
        // is internal and private attribute: we should NOT access it.
        // Unfortunately, there is no good alternative yet to maintain the
        // scroll position. This means we have to be pay double attention when
        // upgrading Turbo.
        // Reference: https://github.com/hotwired/turbo/issues/37#issuecomment-979466543
        Turbo.navigator.currentVisit.scrolled = true;
        disableScroll = false;
    }
});
