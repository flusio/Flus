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

        'via_type' => [
            'type' => 'string',
        ],

        'via_collection_id' => [
            'type' => 'string',
        ],

        'via_link_id' => [
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

        'number_comments' => [
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
            'via_type' => $link->news_via_type,
            'via_link_id' => $link->id,
            'via_collection_id' => $link->news_via_collection_id,
            'is_read' => false,
            'is_removed' => false,
        ]);
    }

    /**
     * @return \flusio\models\Collection|null
     */
    public function viaCollection()
    {
        if (!$this->via_collection_id) {
            return null;
        }

        $collection_dao = new dao\Collection();
        $db_collection = $collection_dao->find($this->via_collection_id);
        return new Collection($db_collection);
    }

    /**
     * @return \flusio\models\Link|null
     */
    public function viaLink()
    {
        if (!$this->via_link_id) {
            return null;
        }

        $link_dao = new dao\Link();
        $db_link = $link_dao->find($this->via_link_id);
        return new Link($db_link);
    }

    /**
     * @return string
     */
    public function host()
    {
        return \flusio\utils\Belt::host($this->url);
    }
}
