<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\models;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RepairLink extends BaseForm
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(
        message: new Translatable('The link is required.'),
    )]
    #[Validable\Url(
        message: new Translatable('The link is invalid.'),
    )]
    public string $url = '';

    #[Form\Field]
    public bool $force_sync = false;

    private ?string $cache_url_cleared = null;

    public function urlCleared(): string
    {
        if ($this->cache_url_cleared === null) {
            $this->cache_url_cleared = \SpiderBits\ClearUrls::clear($this->url);
        }

        return $this->cache_url_cleared;
    }

    public function hasDetectedTrackers(): bool
    {
        return $this->url !== $this->urlCleared();
    }
}
