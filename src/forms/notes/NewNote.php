<?php

namespace App\forms\notes;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;
use Minz\Form;
use Minz\Request;

/**
 * @extends BaseForm<models\Note>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewNote extends BaseForm
{
    use traits\ShareOnMastodon;

    #[Form\Field(transform: 'trim')]
    public string $content = '';
}
