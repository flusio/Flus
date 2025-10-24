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
class EditLinkCollections extends BaseForm
{
    use CollectionsSelector;

    #[Form\Field]
    public bool $is_hidden = false;

    #[Form\Field(bind: false)]
    public bool $mark_as_read = false;

    #[Form\Field(bind: false, transform: 'trim')]
    public string $content = '';

    #[Form\Field(bind: false)]
    public bool $share_on_mastodon = false;

    public function note(): ?models\Note
    {
        if (!$this->content) {
            return null;
        }

        $user = $this->user();
        return new models\Note($user->id, $this->model()->id, $this->content);
    }

    public function isMastodonConfigured(): bool
    {
        $user = $this->user();
        return models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);
    }

    public function shouldShareOnMastodon(): bool
    {
        return $this->isMastodonConfigured() && $this->share_on_mastodon;
    }
}
