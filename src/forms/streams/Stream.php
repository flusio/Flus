<?php

namespace App\forms\streams;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @extends BaseForm<models\Stream>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Stream extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $name = '';

    #[Form\Field(transform: 'trim')]
    public string $description = '';
}
