<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLink extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'title' => [
            'type' => 'string',
            'required' => true,
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Link::validateUrl',
        ],

        'reading_time' => [
            'type' => 'integer',
            'required' => true,
        ],

        'image_filename' => [
            'type' => 'string',
        ],

        'is_read' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'is_removed' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'url_in_bookmarks' => [
            'type' => 'boolean',
            'computed' => true,
        ],

        'url_collections_count' => [
            'type' => 'integer',
            'computed' => true,
        ],
    ];

    /**
     * @param \flusio\models\Link $link
     * @param string $user_id
     *
     * @return \flusio\models\NewsLink
     */
    public static function initFromLink($link, $user_id)
    {
        return new self([
            'title' => $link->title,
            'url' => $link->url,
            'reading_time' => $link->reading_time,
            'image_filename' => $link->image_filename,
            'user_id' => $user_id,
            'is_read' => false,
            'is_removed' => false,
        ]);
    }

    /**
     * Return a Link matching news URL and user_id, if any.
     *
     * @return \flusio\models\Link|null
     */
    public function matchingLink()
    {
        $link_dao = new dao\Link();
        $db_link = $link_dao->findBy([
            'url' => $this->url,
            'user_id' => $this->user_id,
        ]);
        if ($db_link) {
            return new Link($db_link);
        } else {
            return null;
        }
    }

    /**
     * Return the links matching news URL in current user's followed collections.
     *
     * @return \flusio\models\Link[]
     */
    public function matchingFollowedLinks()
    {
        $link_dao = new dao\Link();
        $db_links = $link_dao->listFromFollowedByUrl($this->user_id, $this->url);
        $links = [];
        foreach ($db_links as $db_link) {
            $links[] = new Link($db_link);
        }
        return $links;
    }

    /**
     * @return string
     */
    public function host()
    {
        return \flusio\utils\Belt::host($this->url);
    }
}
