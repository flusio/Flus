import { Application } from 'stimulus';

import HelloController from './controllers/hello_controller.js';

const application = Application.start();
application.register('hello', HelloController);
