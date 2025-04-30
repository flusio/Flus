<?php

namespace App\forms;

use Minz\Form;

/**
 * @template T of object
 *
 * @phpstan-extends Form<T>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BaseForm extends Form
{
    use Form\Csrf;

    public function csrfErrorMessage(): string
    {
        return _('A security verification failed: you should retry to submit the form.');
    }
}
