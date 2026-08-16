<?php

namespace App\forms\views;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\View>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class View extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $name = '';

    public int $name_max_length = models\View::NAME_MAX_LENGTH;
}
