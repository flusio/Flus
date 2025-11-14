<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewLink extends BaseForm
{
    use traits\CollectionsSelector;

    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    public string $url = '';

    #[Form\Field(bind: false)]
    public bool $read_later = true;

    #[Form\Field]
    public bool $is_hidden = false;
}
