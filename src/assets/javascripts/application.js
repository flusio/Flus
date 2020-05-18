import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import HelloController from './controllers/hello_controller.js';

window.jsConfiguration = JSON.parse(document.getElementById('javascript-configuration').innerHTML);

Turbolinks.start();

const application = Application.start();
application.register('hello', HelloController);
