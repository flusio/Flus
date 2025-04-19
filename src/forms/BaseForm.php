<?php

namespace App\forms;

use Minz\Form;

/**
 * @template T of object
 *
 * @phpstan-extends Form<T>
 */
class BaseForm extends Form
{
    use Form\Csrf;

    public function csrfErrorMessage(): string
    {
        return _('A security verification failed: you should retry to submit the form.');
    }
}
