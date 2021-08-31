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

        'published_at' => [
            'type' => 'datetime',
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Link::validateUrl',
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

        'read_at' => [
            'type' => 'datetime',
        ],

        'removed_at' => [
            'type' => 'datetime',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
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
            'published_at' => $link->published_at,
            'url' => $link->url,
            'user_id' => $user_id,
            'link_id' => $link->id,
            'via_type' => $link->via_type,
            'via_collection_id' => $link->via_collection_id,
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
