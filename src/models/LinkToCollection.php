<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links_to_collections')]
class LinkToCollection
{
    use dao\LinkToCollection;
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $collection_id;

    public function __construct(string $link_id, string $collection_id)
    {
        $this->link_id = $link_id;
        $this->collection_id = $collection_id;
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
     * @param string[] $link_ids
     */
    public static function markAsRead(User $user, array $link_ids): void
    {
        $read_list = $user->readList();
        $bookmarks = $user->bookmarks();
        $news = $user->news();

        self::attach($link_ids, [$read_list->id]);
        self::detach($link_ids, [$bookmarks->id, $news->id]);
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
     * @param string[] $link_ids
     */
    public static function markToReadLater(User $user, array $link_ids): void
    {
        $bookmarks = $user->bookmarks();
        $news = $user->news();

        self::attach($link_ids, [$bookmarks->id]);
        self::detach($link_ids, [$news->id]);
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
     * @param string[] $link_ids
     */
    public static function markToNeverRead(User $user, array $link_ids): void
    {
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $never_list = $user->neverList();

        self::attach($link_ids, [$never_list->id]);
        self::detach($link_ids, [$bookmarks->id, $news->id]);
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
     * @param string[] $link_ids
     */
    public static function markAsUnread(User $user, array $link_ids): void
    {
        $read_list = $user->readList();

        self::detach($link_ids, [$read_list->id]);
    }

    /**
     * Attach the collections to the given link and remove old ones if any.
     *
     * This method detaches the link from only collections of type 'collections
     * (i.e. not 'read', 'never', 'news' or 'bookmarks'), even if their ids are
     * missing from $collection_ids. This is because these collections don't
     * appear in the collections selector, when a user changes the collections
     * of a link.
     *
     * @param string[] $collection_ids
     */
    public static function setCollections(string $link_id, array $collection_ids): bool
    {
        $previous_attachments = self::listBy(['link_id' => $link_id]);
        $previous_collection_ids = array_column($previous_attachments, 'collection_id');
        $ids_to_attach = array_diff($collection_ids, $previous_collection_ids);
        $ids_to_detach = array_diff($previous_collection_ids, $collection_ids);

        $database = \Minz\Database::get();
        $database->beginTransaction();

        if ($ids_to_attach) {
            self::attach([$link_id], $ids_to_attach);
        }

        if ($ids_to_detach) {
            self::detachCollections([$link_id], $ids_to_detach);
        }

        return $database->commit();
    }
}
