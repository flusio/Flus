<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkToCollection extends \Minz\Model
{
    use DaoConnector;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'link_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'collection_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * Attach the collections to the given link.
     *
     * Return true if collection_ids is empty.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     * @param \DateTime $created_at Value to set as created_at, "now" by default
     *
     * @return boolean True on success
     */
    public static function attach($link_id, $collection_ids, $created_at = null)
    {
        if (!$collection_ids) {
            return true;
        }

        return self::daoCall('attach', $link_id, $collection_ids, $created_at);
    }

    /**
     * Mark the links as read for the current user.
     *
     * When a link is marked as read, it is added to the user's read list, and
     * removed from its bookmarks and news.
     *
     * You MUST be sure the links are owned by the user when you call this
     * method.
     *
     * @param \flusio\models\User $user
     * @param string[] $link_ids
     */
    public static function markAsRead($user, $link_ids)
    {
        $read_list = $user->readList();
        $bookmarks = $user->bookmarks();
        $news = $user->news();

        foreach ($link_ids as $link_id) {
            self::daoCall('attach', $link_id, [$read_list->id]);
            self::daoCall('detach', $link_id, [$bookmarks->id, $news->id]);
        }
    }

    /**
     * Mark the links to be read later for the current user.
     *
     * When a link is marked to be read later, it is added to the user's
     * bookmarks, and removed from its news.
     *
     * You MUST be sure the links are owned by the user when you call this
     * method.
     *
     * @param \flusio\models\User $user
     * @param string[] $link_ids
     */
    public static function markToReadLater($user, $link_ids)
    {
        $bookmarks = $user->bookmarks();
        $news = $user->news();

        foreach ($link_ids as $link_id) {
            self::daoCall('attach', $link_id, [$bookmarks->id]);
            self::daoCall('detach', $link_id, [$news->id]);
        }
    }

    /**
     * Mark the links never to be read for the current user.
     *
     * When a link is marked never to be read, it is added to the user's never
     * list and removed from its bookmarks and from its news.
     *
     * You MUST be sure the links are owned by the user when you call this
     * method.
     *
     * @param \flusio\models\User $user
     * @param string[] $link_ids
     */
    public static function markToNeverRead($user, $link_ids)
    {
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $never_list = $user->neverList();

        foreach ($link_ids as $link_id) {
            self::daoCall('attach', $link_id, [$never_list->id]);
            self::daoCall('detach', $link_id, [$bookmarks->id, $news->id]);
        }
    }

    /**
     * Mark the links as unread for the current user.
     *
     * When a link is marked as unread, it is removed from the user's read
     * list.
     *
     * You MUST be sure the links are owned by the user when you call this
     * method.
     *
     * @param \flusio\models\User $user
     * @param string[] $link_ids
     */
    public static function markAsUnread($user, $link_ids)
    {
        $read_list = $user->readList();

        foreach ($link_ids as $link_id) {
            self::daoCall('detach', $link_id, [$read_list->id]);
        }
    }

    /**
     * Attach the collections to the given link and remove old ones if any.
     *
     * This method will not detach the link from the read list, nor news, even
     * if their ids are missing from $collection_ids. This is because these
     * collections don't appear in the collections selector, when a user
     * changes the collections of a link.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public static function setCollections($link_id, $collection_ids)
    {
        $previous_attachments = self::listBy(['link_id' => $link_id]);
        $previous_collection_ids = array_column($previous_attachments, 'collection_id');
        $ids_to_attach = array_diff($collection_ids, $previous_collection_ids);
        $ids_to_detach = array_diff($previous_collection_ids, $collection_ids);

        $database = \Minz\Database::get();
        $database->beginTransaction();

        if ($ids_to_attach) {
            self::daoCall('attach', $link_id, $ids_to_attach);
        }

        if ($ids_to_detach) {
            self::daoCall('detachCollections', $link_id, $ids_to_detach);
        }

        return $database->commit();
    }
}
