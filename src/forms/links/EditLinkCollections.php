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
class EditLinkCollections extends BaseForm
{
    use traits\CollectionsSelector;

    #[Form\Field]
    public bool $is_hidden = false;

    #[Form\Field(bind: false)]
    public bool $mark_as_read = false;

    #[Form\Field(bind: false, transform: 'trim')]
    public string $content = '';

    public function note(): ?models\Note
    {
        if (!$this->content) {
            return null;
        }

        $user = $this->optionAs('user', models\User::class);
        return new models\Note($user->id, $this->model()->id, $this->content);
    }
}
