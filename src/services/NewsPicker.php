<?php

namespace flusio\services;

use flusio\models;

/**
 * The NewsPicker service is a sort of basic artificial intelligence. Its
 * purpose is to select a bunch of links relevant for a given user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsPicker
{
    private const DEFAULT_OPTIONS = [
        'number_links' => 9,
        'min_duration' => null,
        'max_duration' => null,
        'from' => 'bookmarks',
    ];

    /** @var \flusio\models\User */
    private $user;

    /** @var array */
    private $options;

    /**
     * @param \flusio\models\User $user
     * @param array $options
     */
    public function __construct($user, $options)
    {
        $this->user = $user;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * Pick and return a set of links relevant for news.
     *
     * @return array
     */
    public function pick()
    {
        if ($this->options['from'] === 'bookmarks') {
            $links = models\Link::listFromBookmarksForNews(
                $this->user->id,
                $this->options['min_duration'],
                $this->options['max_duration'],
            );
        } else {
            $links = models\Link::listFromFollowedCollectionsForNews(
                $this->user->id,
                $this->options['min_duration'],
                $this->options['max_duration'],
            );
        }

        $links = $this->mergeByUrl($links);
        return array_slice($links, 0, $this->options['number_links']);
    }

    /**
     * Removes duplicated links urls.
     *
     * @param models\Link[] $links
     *
     * @return models\Link[]
     */
    private function mergeByUrl(array $links): array
    {
        $by_url = [];
        foreach ($links as $link) {
            $by_url[$link->url] = $link;
        }
        return array_values($by_url);
    }
}
