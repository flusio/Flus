<?php

namespace App\forms\api;

use App\models;
use Minz\Form;

/**
 * @extends Form<models\Collection>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends Form
{
    #[Form\Field(transform: 'trim')]
    public string $name;

    #[Form\Field(transform: 'trim')]
    public string $description;

    #[Form\Field]
    public bool $is_public;
}
