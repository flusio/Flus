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
    public bool $prefill_with_notes = true;

    #[Form\Field(bind: false)]
    public bool $link_to_notes = true;

    #[Form\Field(bind: false)]
    #[Validable\Length(
        max: 100,
        message: 'The text must be less than {max} characters.',
    )]
    public string $post_scriptum = '';

    #[Form\Field(bind: false)]
    public bool $post_scriptum_in_all_posts = false;

    public int $post_scriptum_max_length = 100;

    /**
     * @param array<string, mixed> $default_values
     */
    public function __construct(
        array $default_values = [],
        ?models\MastodonAccount $model = null,
    ) {
        if ($model) {
            $default_values['prefill_with_notes'] = $model->options['prefill_with_notes'];
            $default_values['link_to_notes'] = $model->options['link_to_notes'];
            $default_values['post_scriptum'] = $model->options['post_scriptum'];
            $default_values['post_scriptum_in_all_posts'] = $model->options['post_scriptum_in_all_posts'];
        }

        parent::__construct($default_values, $model);
    }

    #[Form\OnHandleRequest]
    public function setMastodonAccountOptions(Request $request): void
    {
        if ($this->model && !$this->isInvalid()) {
            $this->model->options = [
                'prefill_with_notes' => $this->prefill_with_notes,
                'link_to_notes' => $this->link_to_notes,
                'post_scriptum' => $this->post_scriptum,
                'post_scriptum_in_all_posts' => $this->post_scriptum_in_all_posts,
            ];
        }
    }
}
