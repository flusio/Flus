<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use App\services;
use Minz\Form;
use Minz\Request;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewFeed extends BaseForm
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(
        message: new Translatable('The link is required.'),
    )]
    #[Validable\Url(
        message: new Translatable('The link is invalid.'),
    )]
    public string $url = '';

    private ?models\Link $autodiscovering_link = null;

    public function feed(): models\Collection
    {
        $link = $this->autodiscovering_link;

        if (!$link || count($link->url_feeds) === 0) {
            throw new \LogicException("No feeds found at {$this->url} while checkFeedExists should have failed");
        }

        return models\Collection::findOrBuildFeed($link->url_feeds[0]);
    }

    #[Form\OnHandleRequest]
    public function setAutodiscovering(Request $request): void
    {
        $support_user = models\User::supportUser();
        $link = $support_user->findOrBuildLink($this->url);

        if (!$link->validate()) {
            return;
        }

        $link_fetcher_service = new services\LinkFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
        ]);
        $link_fetcher_service->fetch($link);

        $this->autodiscovering_link = $link;
    }

    #[Validable\Check]
    public function checkFeedExists(): void
    {
        $link = $this->autodiscovering_link;
        if (!$link || count($link->url_feeds) === 0) {
            $this->addError(
                'url',
                'no_feeds',
                _('There is no valid feeds at this address.'),
            );
            return;
        }
    }
}
