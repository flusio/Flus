<?php

namespace App\forms\traits;

use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ShareOnMastodon
{
    #[Form\Field(bind: false)]
    public bool $share_on_mastodon = false;

    public function isMastodonEnabled(): bool
    {
        return $this->options->getBoolean('enable_mastodon');
    }

    public function shouldShareOnMastodon(): bool
    {
        return $this->isMastodonEnabled() && $this->share_on_mastodon;
    }
}
