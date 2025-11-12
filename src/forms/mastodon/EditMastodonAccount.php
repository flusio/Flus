<?php

namespace App\forms\mastodon;

use App\forms\BaseForm;
use App\models;
use Minz\Form;
use Minz\Request;
use Minz\Translatable;
use Minz\Validable;

/**
 * @extends BaseForm<models\MastodonAccount>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditMastodonAccount extends BaseForm
{
    #[Form\Field(bind: false)]
    #[Validable\Presence(
        message: new Translatable('The value is required.'),
    )]
    #[Validable\Inclusion(
        in: ['always', 'never', 'auto'],
        message: new Translatable('The value is invalid.'),
    )]
    public string $link_to_comment = 'auto';

    #[Form\Field(bind: false)]
    #[Validable\Length(
        max: 100,
        message: 'The text must be less than {max} characters.',
    )]
    public string $post_scriptum = '';

    public int $post_scriptum_max_length = 100;

    /**
     * @param array<string, mixed> $default_values
     */
    public function __construct(
        array $default_values = [],
        ?models\MastodonAccount $model = null,
    ) {
        if ($model) {
            $default_values['link_to_comment'] = $model->options['link_to_comment'];
            $default_values['post_scriptum'] = $model->options['post_scriptum'];
        }

        parent::__construct($default_values, $model);
    }

    #[Form\OnHandleRequest]
    public function setMastodonAccountOptions(Request $request): void
    {
        if ($this->model && !$this->isInvalid()) {
            /** @var 'always'|'never'|'auto' */
            $link_to_comment = $this->link_to_comment;

            $this->model->options = [
                'link_to_comment' => $link_to_comment,
                'post_scriptum' => $this->post_scriptum,
            ];
        }
    }
}
