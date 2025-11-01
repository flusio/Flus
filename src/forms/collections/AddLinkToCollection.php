<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AddLinkToCollection extends BaseForm
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    public string $url = '';

    #[Form\Field]
    public bool $is_hidden = false;
}
