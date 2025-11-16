<?php

namespace App\forms\api;

use App\models;
use App\services;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Search extends Form
{
    use utils\Memoizer;

    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(message: 'The link is required.')]
    #[Validable\Url(message: 'The link is invalid.')]
    public string $url = '';

    private models\User $user;

    public function __construct(models\User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    public function link(): models\Link
    {
        return $this->memoize('link', function (): models\Link {
            $link = $this->user->findOrBuildLink($this->url);

            if (!$link->isPersisted()) {
                $link_fetcher_service = new services\LinkFetcher();
                $link_fetcher_service->fetch($link);
                $link->save();
            }

            return $link;
        });
    }

    /**
     * @return models\Collection[]
     */
    public function feeds(): array
    {
        return $this->memoize('feeds', function (): array {
            $link = $this->link();
            $feeds = [];

            $support_user = models\User::supportUser();
            $feed_fetcher_service = new services\FeedFetcher();

            foreach ($link->url_feeds as $feed_url) {
                $feed = models\Collection::findOrBuildFeed($feed_url);

                if (!$feed->isPersisted()) {
                    $feed_fetcher_service->fetch($feed);
                    $feed->save();
                }

                $feeds[] = $feed;
            }

            return $feeds;
        });
    }
}
