<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\forms\traits;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MarkLinkAsReadLater extends BaseForm
{
    use traits\LinkSource;
}
