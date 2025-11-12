<?php

namespace App\forms;

use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    #[Validable\Presence(
        message: new Translatable('The subject is required.'),
    )]
    public string $subject = '';

    #[Form\Field(transform: 'trim')]
    #[Validable\Presence(
        message: new Translatable('The message is required.'),
    )]
    public string $message = '';
}
