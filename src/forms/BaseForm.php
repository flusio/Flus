<?php

namespace App\forms;

use App\auth;
use Minz\Form;

/**
 * @template T of object = \stdClass
 *
 * @phpstan-extends Form<T>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BaseForm extends Form
{
    use Form\Csrf {
        csrfSessionId as protected _csrfSessionId;
    }

    public function csrfErrorMessage(): string
    {
        return _('A security verification failed: you should retry to submit the form.');
    }

    public function csrfSessionId(): string
    {
        $session_id = auth\CurrentUser::sessionToken();
        if ($session_id) {
            return $session_id;
        } else {
            return $this->_csrfSessionId();
        }
    }
}
