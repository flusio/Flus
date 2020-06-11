import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import InputPasswordController from 'js/controllers/input_password_controller.js';
import FormAutosubmitController from 'js/controllers/form_autosubmit_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

Turbolinks.start();

const application = Application.start();
application.register('input-password', InputPasswordController);
application.register('form-autosubmit', FormAutosubmitController);
