import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import ConfirmationController from 'js/controllers/confirmation_controller.js';
import InputPasswordController from 'js/controllers/input_password_controller.js';
import FormAutosubmitController from 'js/controllers/form_autosubmit_controller.js';
import LinkFetcherController from 'js/controllers/link_fetcher_controller.js';
import PopupController from 'js/controllers/popup_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

Turbolinks.start();

const application = Application.start();
application.register('confirmation', ConfirmationController);
application.register('input-password', InputPasswordController);
application.register('form-autosubmit', FormAutosubmitController);
application.register('link-fetcher', LinkFetcherController);
application.register('popup', PopupController);
