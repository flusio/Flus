<?php

namespace App\forms;

use App\models;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Locale extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    #[Validable\Presence(
        message: new Translatable('The locale is required.'),
    )]
    #[models\checks\Locale(
        message: new Translatable('The locale is invalid.'),
    )]
    public string $locale = '';
}
