<?php

namespace App\forms\traits;

use Minz\Form;
use Minz\Request;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Redirectable
{
    #[Form\Field]
    public string $redirect_to = '';

    #[Form\OnHandleRequest]
    public function forceRedirectableRedirectTo(Request $request): void
    {
        $router = \Minz\Engine::router();
        if (!$router->isRedirectable($this->redirect_to)) {
            $this->redirect_to = \Minz\Url::for('home');
        }
    }
}
