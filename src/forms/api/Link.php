<?php

namespace App\forms\api;

use App\models;
use Minz\Form;

/**
 * @extends Form<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Link extends Form
{
    #[Form\Field(transform: 'trim')]
    public string $title;

    #[Form\Field]
    public int $reading_time;
}
