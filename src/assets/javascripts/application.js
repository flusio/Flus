import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import BackAnchorController from 'js/controllers/back_anchor_controller.js';
import BackStorageController from 'js/controllers/back_storage_controller.js';
import CollectionsSelectorController from 'js/controllers/collections_selector_controller.js';
import ConfirmationController from 'js/controllers/confirmation_controller.js';
import CopyToClipboardController from 'js/controllers/copy_to_clipboard_controller.js';
import InputPasswordController from 'js/controllers/input_password_controller.js';
import FormAutosubmitController from 'js/controllers/form_autosubmit_controller.js';
import LinkCardController from 'js/controllers/link_card_controller.js';
import LinkFetcherController from 'js/controllers/link_fetcher_controller.js';
import ModalController from 'js/controllers/modal_controller.js';
import ModalOpenerController from 'js/controllers/modal_opener_controller.js';
import PopupController from 'js/controllers/popup_controller.js';
import SkipNavController from 'js/controllers/skip_nav_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

Turbolinks.start();

const application = Application.start();
application.register('back-anchor', BackAnchorController);
application.register('back-storage', BackStorageController);
application.register('collections-selector', CollectionsSelectorController);
application.register('confirmation', ConfirmationController);
application.register('copy-to-clipboard', CopyToClipboardController);
application.register('input-password', InputPasswordController);
application.register('form-autosubmit', FormAutosubmitController);
application.register('link-card', LinkCardController);
application.register('link-fetcher', LinkFetcherController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('popup', PopupController);
application.register('skip-nav', SkipNavController);

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

document.addEventListener('turbolinks:load', adaptLayoutContentBorderRadius);
