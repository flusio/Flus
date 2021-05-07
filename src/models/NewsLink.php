<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLink extends \Minz\Model
{
    use DaoConnector;

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

        'link_id' => [
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
            'link_id' => $link->id,
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
        return Collection::find($this->via_collection_id);
    }

    /**
     * Return the title of associated link, or url if link no longer exists.
     *
     * @return string
     */
    public function title()
    {
        $link = Link::find($this->link_id);
        if ($link) {
            return $link->title;
        } else {
            return $this->url;
        }
    }
}
