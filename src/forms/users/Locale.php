<?php

namespace App\forms\users;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Locale extends BaseForm
{
    use traits\Locale;
}
