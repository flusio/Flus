<?php

namespace App\forms;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;
use Minz\Translatable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Search extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    #[Validable\Presence(
        message: new Translatable('The link is required.')
    )]
    #[Validable\Url(
        message: new Translatable('The link is invalid.'),
    )]
    public string $url = '';

    #[Form\Field]
    public bool $autosubmit = false;

    public function link(): models\Link
    {
        return $this->memoize('link', function (): models\Link {
            $user = $this->optionAs('user', models\User::class);

            return $user->findOrBuildLink($this->url);
        });
    }

    public function existingLink(): ?models\Link
    {
        return $this->memoize('existing_link', function (): ?models\Link {
            $user = $this->optionAs('user', models\User::class);

            return models\Link::findComputedBy([
                'user_id' => $user->id,
                'url_hash' => models\Link::hashUrl($this->url),
            ], ['number_notes']);
        });
    }

    /**
     * @return models\Collection[]
     */
    public function existingFeeds(): array
    {
        return $this->memoize('existing_feeds', function (): array {
            $link = $this->existingLink();
            $feeds = [];

            if ($link) {
                $link_feeds = models\Collection::listComputedFeedsByFeedUrls(
                    $link->url_feeds,
                    ['number_links']
                );

                // Deduplicate feeds with same names
                foreach ($link_feeds as $feed) {
                    if (!isset($feeds[$feed->name])) {
                        $feeds[$feed->name] = $feed;
                    }
                }
            }

            return $feeds;
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

            foreach ($link->url_feeds as $feed_url) {
                $feeds[] = models\Collection::findOrBuildFeed($feed_url);
            }

            return $feeds;
        });
    }
}
