<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\forms\ShareOnMastodon;
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
    use CollectionsSelector;
    use ShareOnMastodon;

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

        $user = $this->user();
        return new models\Note($user->id, $this->model()->id, $this->content);
    }
}
