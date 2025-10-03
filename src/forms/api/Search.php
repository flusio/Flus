<?php

namespace App\forms\api;

use App\models;
use App\services;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Search extends Form
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(message: 'The link is required.')]
    #[Validable\Url(message: 'The link is invalid.')]
    public string $url = '';

    private models\User $user;

    private ?models\Link $cached_link = null;

    /** @var ?models\Collection[] */
    private ?array $cached_feeds = null;

    public function __construct(models\User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    public function link(): models\Link
    {
        if ($this->cached_link !== null) {
            return $this->cached_link;
        }

        $link = models\Link::findBy([
            'user_id' => $this->user->id,
            'url_hash' => models\Link::hashUrl($this->url),
        ]);

        if (!$link) {
            $link = new models\Link($this->url, $this->user->id);

            $link_fetcher_service = new services\LinkFetcher();
            $link_fetcher_service->fetch($link);
        }

        $this->cached_link = $link;

        return $link;
    }

    /**
     * @return models\Collection[]
     */
    public function feeds(): array
    {
        if ($this->cached_feeds !== null) {
            return $this->cached_feeds;
        }

        $link = $this->link();
        $feeds = [];

        $support_user = models\User::supportUser();
        $feed_fetcher_service = new services\FeedFetcher();

        foreach ($link->url_feeds as $feed_url) {
            $feed = models\Collection::findBy([
                'type' => 'feed',
                'feed_url' => $feed_url,
                'user_id' => $support_user->id,
            ]);

            if (!$feed) {
                $feed = models\Collection::initFeed($support_user->id, $feed_url);
                $feed_fetcher_service->fetch($feed);
            }

            $feeds[] = $feed;
        }

        $this->cached_feeds = $feeds;

        return $feeds;
    }
}
