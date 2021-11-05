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
        $link_dao = new models\dao\Link();

        if ($this->options['from'] === 'bookmarks') {
            $db_links = $link_dao->listFromBookmarksForNews(
                $this->user->id,
                $this->options['min_duration'],
                $this->options['max_duration'],
            );
        } else {
            $db_links = $link_dao->listFromFollowedCollectionsForNews(
                $this->user->id,
                $this->options['min_duration'],
                $this->options['max_duration'],
            );
        }

        $db_links = $this->mergeByUrl($db_links);
        return array_slice($db_links, 0, $this->options['number_links']);
    }

    /**
     * Removes duplicated links urls.
     *
     * @param array $db_links
     *
     * @return array
     */
    private function mergeByUrl($db_links)
    {
        $by_url = [];
        foreach ($db_links as $db_link) {
            $url = $db_link['url'];
            $by_url[$url] = $db_link;
        }
        return array_values($by_url);
    }
}
