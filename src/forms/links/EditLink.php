<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * @extends BaseForm<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditLink extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $title = '';

    #[Form\Field]
    public int $reading_time = 0;

    #[Form\Field(bind: 'setOrigin')]
    public string $origin = '';

    #[Form\Field]
    public bool $origin_is_public = false;

    public int $origin_max_length = models\Link::ORIGIN_MAX_LENGTH;
}
