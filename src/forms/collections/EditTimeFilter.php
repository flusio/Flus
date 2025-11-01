<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\FollowedCollection>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditTimeFilter extends BaseForm
{
    #[Form\Field]
    public string $time_filter;
}
