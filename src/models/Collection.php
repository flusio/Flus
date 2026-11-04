<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * A list of links curated by a user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends Source
{
    use link_lists\Discoverable;
    use link_lists\OwnedByUser;
    use link_lists\Shareable;

    #[Database\Column]
    public ?string $group_id = null;

    public static function init(
        User $user,
        string $name = '',
        string $description = '',
        bool $is_public = false,
    ): self {
        $collection = new self();

        $collection->type = 'collection';
        $collection->name = trim($name);
        $collection->description = trim($description);
        $collection->is_public = $is_public;
        $collection->user_id = $user->id;

        return $collection;
    }

    /**
     * Return the URL of the feed to subscribe to the collection.
     *
     * It corresponds to the "source feed" route. $direct indicates if the
     * links of the generated feed should point directly to the external
     * websites.
     */
    public function feedUrl(bool $direct = false): string
    {
        $parameters = ['id' => $this->id];

        if ($direct) {
            $parameters['direct'] = 'true';
        }

        return \Minz\Url::absoluteFor('source feed', $parameters);
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->owner());
        return $markdown->text($this->description);
    }

    /**
     * Return the group if any.
     */
    public function group(): ?Group
    {
        if (!$this->group_id) {
            return null;
        }

        return Group::require($this->group_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function toJson(User $context_user): array
    {
        $group = $context_user->isOwningCollection($this) ? $this->group() : null;

        return [
            'id' => $this->id,
            'name' => $this->nameByUser($context_user),
            'description' => $this->description,
            'group' => $group?->name,
            'is_public' => $this->is_public,
            'is_followed' => $context_user->isFollowing($this),
            'publication_frequency_per_year' => $this->publication_frequency_per_year,
        ];
    }

    /**
     * Return a link from this collection with the given URL and not owned by
     * the given user.
     */
    public function linkNotOwnedByUrl(User $user, string $url_hash): ?Link
    {
        return Link::findNotOwnedByCollectionAndUrl(
            $user,
            $this,
            $url_hash,
        );
    }

    /**
     * Return the list of collections attached to the given link.
     *
     * @return self[]
     */
    public static function listByLink(Link $link): array
    {
        return self::listByLinks([$link])[$link->id] ?? [];
    }

    /**
     * Return the list of collections attached to the given links, indexed by
     * the link ids.
     *
     * The links without any collection are absent from the returned array. A
     * collection attached to several links is represented by a single object,
     * shared by these links.
     *
     * @param Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self[]>
     */
    public static function listByLinks(array $links, int $chunk_size = 1000): array
    {
        $link_ids = array_column($links, 'id');
        $link_ids = array_unique($link_ids);
        $link_ids = array_values($link_ids);

        $collections_by_ids = [];
        $collections_by_link_ids = [];

        foreach (array_chunk($link_ids, $chunk_size) as $chunk_link_ids) {
            $ids_as_question_marks = array_fill(0, count($chunk_link_ids), '?');
            $ids_where_statement = implode(', ', $ids_as_question_marks);

            $sql = <<<SQL
                SELECT lc.link_id, c.*
                FROM collections c, links_to_collections lc

                WHERE lc.collection_id = c.id
                AND lc.link_id IN ({$ids_where_statement})
                AND c.type = 'collection'
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($chunk_link_ids);

            foreach ($statement->fetchAll() as $row) {
                // link_id is not a column of Collection: it must be removed
                // from the row before the model is built from it.
                $link_id = strval($row['link_id']);
                unset($row['link_id']);

                $collection_id = strval($row['id']);
                $collection = $collections_by_ids[$collection_id] ?? self::fromDatabaseRow($row);
                $collections_by_ids[$collection_id] = $collection;

                $collections_by_link_ids[$link_id][] = $collection;
            }
        }

        return $collections_by_link_ids;
    }

    /**
     * Return the collections of the given user with its computed properties.
     *
     * @param User $user
     *     The user that the collections must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'private'?: bool,
     *     'count_hidden'?: bool,
     * } $options
     *
     * Description of the options:
     * - private (default to true), indicates if private collections must be
     *   included. If private are excluded and number_links property is
     *   selected, empty public collections are not returned either.
     * - count_hidden (default to true), indicates if hidden links must be
     *   counted
     *
     * @return self[]
     */
    public static function listComputedByUser(
        User $user,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'private' => true,
            'count_hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user->id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.id) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';

            if (!$options['count_hidden']) {
                $number_links_clause = ', COUNT(l.id) AS number_links';

                $join_clause .= <<<SQL
                    \n
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $private_clause = '';
        $non_empty_clause = '';
        if (!$options['private']) {
            $private_clause = 'AND c.is_public = true';

            if (in_array('number_links', $selected_computed_props)) {
                $non_empty_clause = 'HAVING COUNT(lc.id) > 0';

                if (!$options['count_hidden']) {
                    $non_empty_clause = 'HAVING COUNT(l.id) > 0';
                }
            }
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collections c

            {$join_clause}

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            {$private_clause}

            {$group_by_clause}

            {$non_empty_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return whether the link is in a collection owned by the given user or not.
     */
    public static function existsForUserAndLink(User $user, Link $link): bool
    {
        $sql = <<<'SQL'
            SELECT EXISTS (
                SELECT 1
                FROM collections c, links_to_collections lc

                WHERE c.id = lc.collection_id
                AND c.user_id = :user_id
                AND lc.link_id = :link_id
                AND c.type = 'collection'
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
            ':link_id' => $link->id,
        ]);
        return (bool) $statement->fetchColumn();
    }

    /**
     * Return the number of public collections.
     */
    public static function countPublic(): int
    {
        return self::countBy([
            'is_public' => true,
        ]);
    }
}
