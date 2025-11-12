<?php

namespace App\forms\groups;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\Group>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Group extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $name = '';

    public int $group_name_max_length = models\Group::NAME_MAX_LENGTH;
}
