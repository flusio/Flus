import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import CollectionsSelectorController from 'js/controllers/collections_selector_controller.js';
import ConfirmationController from 'js/controllers/confirmation_controller.js';
import CopyToClipboardController from 'js/controllers/copy_to_clipboard_controller.js';
import InputPasswordController from 'js/controllers/input_password_controller.js';
import FormAutosubmitController from 'js/controllers/form_autosubmit_controller.js';
import LinkBackPageController from 'js/controllers/link_back_page_controller.js';
import LinkCardController from 'js/controllers/link_card_controller.js';
import LinkFetcherController from 'js/controllers/link_fetcher_controller.js';
import ModalController from 'js/controllers/modal_controller.js';
import ModalOpenerController from 'js/controllers/modal_opener_controller.js';
import PopupController from 'js/controllers/popup_controller.js';
import SkipNavController from 'js/controllers/skip_nav_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

Turbolinks.start();

const application = Application.start();
application.register('collections-selector', CollectionsSelectorController);
application.register('confirmation', ConfirmationController);
application.register('copy-to-clipboard', CopyToClipboardController);
application.register('input-password', InputPasswordController);
application.register('form-autosubmit', FormAutosubmitController);
application.register('link-back-page', LinkBackPageController);
application.register('link-card', LinkCardController);
application.register('link-fetcher', LinkFetcherController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('popup', PopupController);
application.register('skip-nav', SkipNavController);

document.addEventListener('turbolinks:visit', (event) => {
    // This is used for the "back" button on link main page. This allows to go
    // to the page that brought the user on the page.
    // See link-back-page controller to see how it is used.
    const currentPath = window.location.pathname;
    const collectionRegex = /\/collections\/\w+/;
    if (
        currentPath === '/news' ||
        currentPath === '/bookmarks' ||
        currentPath.match(collectionRegex)
    ) {
        window.localStorage.setItem('backForLink', currentPath);
    }
});
