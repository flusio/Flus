<?php

namespace App\forms\api;

use App\models;
use Minz\Form;

/**
 * @extends Form<models\Note>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Note extends Form
{
    #[Form\Field(transform: 'trim')]
    public string $content;
}
