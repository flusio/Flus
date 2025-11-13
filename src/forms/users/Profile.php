<?php

namespace App\forms\users;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Profile extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $username = '';

    public int $username_max_length = models\User::USERNAME_MAX_LENGTH;
}
