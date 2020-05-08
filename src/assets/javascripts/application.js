import Turbolinks from 'turbolinks';
import { Application } from 'stimulus';

import HelloController from './controllers/hello_controller.js';

Turbolinks.start();

const application = Application.start();
application.register('hello', HelloController);
