<?php

namespace App\models\links;

use App\models\User;
use App\utils\Belt;
use Minz\Database;

/**
 * Add the relation between a link and the user who owns it.
 *
 * A link can have no owner: it is then a link that the server knows about, but
 * that nobody added to their own links.
 *
 * This trait requires utils\Memoizer.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OwnedByUser
{
    #[Database\Column]
    public ?string $user_id = null;

    /**
     * Return a link with the given URL owned by the user. Any matching link
     * that already exists in the database is returned.
     *
     * If no users are passed, the method looks for a link without any owner.
     *
     * The URL is sanitized before being searched, so you don't have to do it
     * yourself.
     */
    public static function findOrBuildByUrl(string $url, ?User $user = null): self
    {
        $url = \SpiderBits\Url::sanitize($url);
        $user_id = $user?->id;

        $link = self::findBy([
            'user_id' => $user_id,
            'url_hash' => Belt::hashUrl($url),
        ]);

        if (!$link) {
            $link = new self($url, $user, is_hidden: false);
        }

        return $link;
    }

    /**
     * Copy a Link to the given user.
     */
    public static function copy(self $link, User $user): self
    {
        $link_copied = new self($link->url, $user, false);

        $link_copied->title = $link->title;
        $link_copied->url_feeds = $link->url_feeds;
        $link_copied->url_replies = $link->url_replies;
        $link_copied->image_filename = $link->image_filename;
        $link_copied->reading_time = $link->reading_time;
        $link_copied->fetched_at = $link->fetched_at;
        $link_copied->fetched_code = $link->fetched_code;
        $link_copied->fetched_count = $link->fetched_count;
        $link_copied->fetched_retry_at = $link->fetched_retry_at;

        return $link_copied;
    }

    /**
     * Return the owner of the link if any.
     */
    public function owner(): ?User
    {
        return $this->memoize('owner', function (): ?User {
            if (!$this->user_id) {
                return null;
            }

            return User::find($this->user_id);
        });
    }

    /**
     * Set the owner of the link.
     *
     * The owner is memoized so it doesn't have to be loaded from the database
     * by owner().
     */
    public function setOwner(?User $user): void
    {
        $this->user_id = $user?->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * Return links of the given user with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * Also, if unshared links are excluded, links are returned on the base of
     * their relation with collections. It means that published_at will be set
     * to the date of attachment of the related collection. If a link is
     * attached to multiple collections, it could potentially return the same
     * link several times with different published_at. However, the method
     * takes care of it and will return the link only once by taking the most
     * recent attachment.
     *
     * You may be affraid by this method and you would be right. This is the
     * price to pay to return not duplicated and ordered links with their
     * computed properties.
     *
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'unshared'?: bool,
     *     'tag'?: string,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * Description of the options:
     *
     * - unshared (default to true), indicates if unshared links must be
     *   included. Shared links are visible and are included in one public
     *   collection at least.
     * - tag, to filter links by the given tag.
     * - offset (default to 0), the offset for pagination
     * - limit (default to 'ALL') the limit for pagination
     *
     * @return self[]
     */
    public static function listComputedByUser(
        User $user,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'unshared' => true,
            'tag' => '',
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user->id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
            $order_by_clause = 'ORDER BY published_at DESC, l.id';
        }

        $number_notes_clause = '';
        if (in_array('number_notes', $selected_computed_props)) {
            $number_notes_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM notes m
                    WHERE m.link_id = l.id
                ) AS number_notes
            SQL;
        }

        $visibility_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (!$options['unshared']) {
            $visibility_clause = 'AND l.is_hidden = false';
            $join_clause = <<<SQL
                INNER JOIN links_to_collections lc
                ON lc.link_id = l.id

                INNER JOIN collections c
                ON lc.collection_id = c.id
                AND c.is_public = true
                AND c.user_id = :user_id
            SQL;

            if (in_array('published_at', $selected_computed_props)) {
                $published_at_clause = ', MAX(lc.created_at) AS published_at';
                $group_by_clause = 'GROUP BY l.id';
            }
        }

        $tag_clause = '';
        if ($options['tag']) {
            $tag_clause = 'AND l.tags ?? :tag';
            $parameters[':tag'] = mb_strtolower($options['tag']);
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_notes_clause}
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}
            {$tag_clause}

            {$group_by_clause}
            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Count links of the given user.
     *
     * @param array{
     *     'unshared'?: bool,
     *     'tag'?: string,
     * } $options
     *
     * Description of the options:
     *
     * - unshared (default to true), indicates if unshared links must be
     *   included. Shared links are visible and are included in one public
     *   collection at least.
     * - tag, to filter links by the given tag.
     */
    public static function countByUser(
        User $user,
        array $options = [],
    ): int {
        $default_options = [
            'unshared' => true,
            'tag' => '',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user->id,
        ];

        $visibility_clause = '';
        $join_clause = '';
        if (!$options['unshared']) {
            $visibility_clause = 'AND l.is_hidden = false';
            $join_clause = <<<SQL
                INNER JOIN links_to_collections lc
                ON lc.link_id = l.id

                INNER JOIN collections c
                ON lc.collection_id = c.id
                AND c.is_public = true
                AND c.user_id = :user_id
            SQL;
        }

        $tag_clause = '';
        if ($options['tag']) {
            $tag_clause = 'AND l.tags ?? :tag';
            $parameters[':tag'] = mb_strtolower($options['tag']);
        }

        $sql = <<<SQL
            SELECT COUNT(distinct l.id)
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}
            {$tag_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * Return a list of suggested links for the user.
     *
     * Suggested links have the same URL as the given one, but are from
     * other users if they added notes to them.
     *
     * @return self[]
     */
    public static function listSuggestedFor(User $user, self $link): array
    {
        $sql = <<<SQL
            SELECT l.* FROM links l

            -- Select the links with the same URL but not owned by the current
            -- user, and not the current link.
            WHERE l.url_hash = :url_hash
            AND l.user_id IS DISTINCT FROM :user_id
            AND l.id != :link_id

            AND EXISTS (
                -- Only if it's present in a collection...
                SELECT 1 FROM links_to_collections lc

                WHERE lc.link_id = l.id
                AND lc.collection_id IN (
                    -- ... owned by the user...
                    SELECT c.id FROM collections c WHERE c.user_id = :user_id
                    UNION
                    -- ... or shared with the user.
                    SELECT cs.collection_id FROM collection_shares cs WHERE cs.user_id = :user_id
                )
            )

            -- And only if there are notes attached to the links.
            AND EXISTS (
                SELECT 1 FROM notes n
                WHERE n.link_id = l.id
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':url_hash' => $link->url_hash,
            ':link_id' => $link->id,
            ':user_id' => $user->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    public function numberCollectionsForUser(User $user): int
    {
        return $this->memoize("number_collections_{$user->id}", function () use ($user): int {
            $sql = <<<SQL
                SELECT COUNT(c.*)
                FROM collections c, links_to_collections lc, links l

                WHERE l.url_hash = :link_url_hash
                AND l.user_id = :user_id

                AND lc.link_id = l.id
                AND lc.collection_id = c.id

                AND c.type = 'collection'
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute([
                ':link_url_hash' => $this->url_hash,
                ':user_id' => $user->id,
            ]);

            return intval($statement->fetchColumn());
        });
    }

    /**
     * Return links of the given user which have at least one note.
     *
     * @return self[]
     */
    public static function listByUserWithNotes(User $user): array
    {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, notes n

            WHERE l.id = n.link_id
            AND l.user_id = :user_id

            ORDER BY l.created_at DESC, l.id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the list of url ids indexed by urls for the given user.
     *
     * @return array<string, string>
     */
    public static function listUrlsToIdsByUser(User $user): array
    {
        $sql = <<<SQL
            SELECT url, id FROM links
            WHERE user_id = :user_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
