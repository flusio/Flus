<?php

namespace App\forms\links;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RepairLink extends BaseForm
{
    use utils\Memoizer;

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

    public function urlCleared(): string
    {
        return $this->memoize('url_cleared', function (): string {
            return \SpiderBits\ClearUrls::clear($this->url);
        });
    }

    public function hasDetectedTrackers(): bool
    {
        return $this->url !== $this->urlCleared();
    }
}
