<?php

namespace App\forms;

use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Locale extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $locale = '';
}
