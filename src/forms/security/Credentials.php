<?php

namespace App\forms\security;

use App\forms\BaseForm;
use App\forms\DemoDisabler;
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
class Credentials extends BaseForm
{
    use DemoDisabler;

    #[Form\Field(bind: 'setEmail')]
    public string $email = '';

    #[Form\Field(bind: 'changePassword')]
    public string $password = '';
}
