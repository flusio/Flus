import * as Turbo from '@hotwired/turbo'; // eslint-disable-line no-unused-vars
import { Application } from '@hotwired/stimulus';

import AutosubmitController from 'js/controllers/autosubmit_controller.js';
import BackButtonController from 'js/controllers/back_button_controller.js';
import CollectionsSelectorController from 'js/controllers/collections_selector_controller.js';
import ConfirmationController from 'js/controllers/confirmation_controller.js';
import CopyToClipboardController from 'js/controllers/copy_to_clipboard_controller.js';
import CsrfLoaderController from 'js/controllers/csrf_loader_controller.js';
import FormFileController from 'js/controllers/form_file_controller.js';
import GroupSelectorController from 'js/controllers/group_selector_controller.js';
import InputPasswordController from 'js/controllers/input_password_controller.js';
import ModalController from 'js/controllers/modal_controller.js';
import ModalOpenerController from 'js/controllers/modal_opener_controller.js';
import PocketOptionController from 'js/controllers/pocket_option_controller.js';
import PopupController from 'js/controllers/popup_controller.js';
import TextEditorController from 'js/controllers/text_editor_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

const application = Application.start();
application.register('autosubmit', AutosubmitController);
application.register('back-button', BackButtonController);
application.register('collections-selector', CollectionsSelectorController);
application.register('confirmation', ConfirmationController);
application.register('copy-to-clipboard', CopyToClipboardController);
application.register('csrf-loader', CsrfLoaderController);
application.register('form-file', FormFileController);
application.register('group-selector', GroupSelectorController);
application.register('input-password', InputPasswordController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('pocket-option', PocketOptionController);
application.register('popup', PopupController);
application.register('text-editor', TextEditorController);

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
