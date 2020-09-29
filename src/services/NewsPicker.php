<?php

namespace flusio\services;

/**
 * The NewsPicker service is a sort of basic artificial intelligence. Its
 * purpose is to select a bunch of links relevant for a given user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsPicker
{
    public const MIN_DURATION_RATIO = 0.9;
    public const MAX_DURATION_RATIO = 1.2;

    public const BOOKMARKS_BASE_VALUE = 3;
    public const FOLLOWED_BASE_VALUE = 2;
    public const TOPICS_BASE_VALUE = 1;

    /** @var \flusio\models\User */
    private $user;

    /** @var \flusio\models\NewsPreferences */
    private $preferences;

    /**
     * @param \flusio\models\User
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->preferences = \flusio\models\NewsPreferences::fromJson($user->news_preferences);
    }

    /**
     * Pick and return a set of links relevant for news.
     *
     * @return array
     */
    public function pick()
    {
        $link_dao = new \flusio\models\dao\Link();
        $bookmarks_db_links = [];
        $followed_db_links = [];
        $topics_db_links = [];

        if ($this->preferences->from_bookmarks) {
            $bookmarks_db_links = $link_dao->listFromBookmarksForNews($this->user->id);
            $bookmarks_db_links = $this->assignNewsValue(
                $bookmarks_db_links,
                self::BOOKMARKS_BASE_VALUE
            );
        }

        if ($this->preferences->from_followed) {
            $followed_db_links = $link_dao->listFromFollowedCollectionsForNews($this->user->id);
            $followed_db_links = $this->assignNewsValue(
                $followed_db_links,
                self::FOLLOWED_BASE_VALUE
            );
        }

        if ($this->preferences->from_topics) {
            $topics_db_links = $link_dao->listFromTopicsForNews($this->user->id);
            $topics_db_links = $this->assignNewsValue(
                $topics_db_links,
                self::TOPICS_BASE_VALUE
            );
        }

        $db_links = array_merge($bookmarks_db_links, $followed_db_links, $topics_db_links);
        $db_links = $this->mergeByUrl($db_links);

        return $this->greedyCollect($db_links);
    }

    /**
     * Assign a news_value items to the db_links, which is used to compare the
     * links together.
     *
     * @param array $db_links
     * @param integer $base_value
     *
     * @return array
     */
    private function assignNewsValue($db_links, $base_value)
    {
        $result = [];
        foreach ($db_links as $db_link) {
            $db_link['news_value'] = random_int(0, $base_value);
            $result[] = $db_link;
        }
        return $result;
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

    /**
     * Collect the links in order to optimize their total news_value, while
     * keeping their total reading time under a max duration.
     *
     * @param array $db_links
     *
     * @return array
     */
    private function greedyCollect($db_links)
    {
        $asked_duration = $this->preferences->duration;
        $selected_db_links = [];
        $duration = 0;

        // Sort links by their values. We don't sort by their efficiency
        // (i.e. value/reading_time) because it would disadvantage long
        // articles.
        usort($db_links, function ($db_link1, $db_link2) {
            return $db_link2['news_value'] <=> $db_link1['news_value'];
        });

        // Collect the links so as their total reading_time is between a
        // minimum and a maximum durations.
        foreach ($db_links as $db_link) {
            $new_duration = $duration + $db_link['reading_time'];
            if ($new_duration <= $asked_duration * self::MAX_DURATION_RATIO) {
                $selected_db_links[] = $db_link;
                $duration = $new_duration;
            }

            if ($duration >= $asked_duration * self::MIN_DURATION_RATIO) {
                break;
            }
        }

        return $selected_db_links;
    }
}
