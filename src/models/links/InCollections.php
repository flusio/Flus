<?php

namespace App\models\links;

use App\models\Collection;
use App\models\CollectionShare;
use App\models\LinkToCollection;
use App\models\User;
use Minz\Database;

/**
 * Add the relation between a link and the collections that contain it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InCollections
{
    /**
     * Return the collections attached to the current link
     *
     * @return Collection[]
     */
    public function collections(): array
    {
        return Collection::listByLinkId($this->id);
    }

    /**
     * Set the link's collections.
     *
     * @param Collection[] $collections
     */
    public function setCollections(
        array $collections,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::setCollections($this->id, $collection_ids, $at);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Add the link to the collections.
     *
     * @param Collection[] $collections
     */
    public function addCollections(
        array $collections,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::attach([$this->id], $collection_ids, $at);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Add the link to a collection.
     */
    public function addCollection(
        Collection $collection,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $this->addCollections([$collection], $at, $sync_publication_frequency);
    }

    /**
     * Remove the link from the collections.
     *
     * @param Collection[] $collections
     */
    public function removeCollections(
        array $collections,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::detach([$this->id], $collection_ids);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Remove the link from a collection.
     */
    public function removeCollection(
        Collection $collection,
        bool $sync_publication_frequency = true,
    ): void {
        $this->removeCollections([$collection], $sync_publication_frequency);
    }

    /**
     * Return the source of the link.
     */
    public function source(): ?Collection
    {
        if (!$this->source_id) {
            return null;
        }
        return Collection::find($this->source_id);
    }

    /**
     * Return whether the link is shared with the given user or not (i.e. it is
     * attached to a shared collection or to an owned collection).
     *
     * If $access_type is 'any' or 'read', the method returns true just if a
     * collection_share exists for this collection and user.
     *
     * If $access_type is 'write', the method will check that the collection
     * share has a 'write' type.
     *
     * $access_type has no effect if a link is in an owned collection (i.e. it
     * implies the user has write effect over it).
     */
    public function sharedWith(User $user, string $access_type = 'any'): bool
    {
        return (
            Collection::existsForUserIdAndLinkId($user->id, $this->id) ||
            CollectionShare::existsForUserIdAndLinkId($user->id, $this->id, $access_type)
        );
    }

    /**
     * Return links of the given collection with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'published_date'?: ?\DateTimeImmutable,
     *     'source'?: ?string,
     *     'hidden'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * Description of the options:
     *
     * - published_date (default to null), limits the selection to the given publication date
     * - source (default to null), limits the selection to the given source
     * - hidden (default to true), indicates if hidden links must be included
     * - offset (default to 0), the offset for pagination
     * - limit (default to 'ALL') the limit for pagination
     *
     * @return self[]
     */
    public static function listComputedByCollection(
        Collection $collection,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'published_date' => null,
            'source' => null,
            'hidden' => true,
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection->id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', lc.created_at AS published_at';
            $order_by_clause = 'ORDER BY lc.created_at DESC, l.id';
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

        $date_clause = '';
        if ($options['published_date'] !== null) {
            $date_clause = "AND lc.created_at >= :published_start AND lc.created_at <= :published_end";

            $start = $options['published_date']->modify('00:00:00');
            $end = $start->modify('23:59:59');

            $parameters[':published_start'] = $start->format(Database\Column::DATETIME_FORMAT);
            $parameters[':published_end'] = $end->format(Database\Column::DATETIME_FORMAT);
        }

        $source = $options['source'];
        $source_clause = '';
        if ($source) {
            $source_clause = 'AND source_id = :source';
            $parameters[':source'] = $source;
        }

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
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
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id
            {$date_clause}
            {$source_clause}
            {$visibility_clause}

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
     * Count links of the given collection.
     *
     * @param array{
     *     'hidden'?: bool,
     *     'since'?: \DateTimeImmutable,
     * } $options
     *
     * Description of the options:
     *
     * - hidden (default to true), indicates if hidden links must be included
     * - since (default to null), counts links that have been added since the
     *   given date only
     */
    public static function countByCollection(Collection $collection, array $options = []): int
    {
        $default_options = [
            'hidden' => true,
            'since' => null,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection->id,
        ];

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $since_clause = '';
        if ($options['since']) {
            $since_clause = 'AND lc.created_at >= :since';
            $parameters[':since'] = $options['since']->format(Database\Column::DATETIME_FORMAT);
        }

        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$since_clause}

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * Find a link by its URL and collection but not owned by the given user.
     */
    public static function findNotOwnedByCollectionAndUrl(
        User $user,
        Collection $collection,
        string $url_hash,
    ): ?self {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, links_to_collections lc

            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id

            AND l.url_hash = :url_hash
            AND l.user_id IS DISTINCT FROM :user_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
            ':collection_id' => $collection->id,
            ':url_hash' => $url_hash,
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return the oldest publication date in the collection since the given date.
     */
    public static function getOldestPublicationDateSince(
        Collection $collection,
        \DateTimeImmutable $since
    ): ?\DateTimeImmutable {
        $sql = <<<SQL
            SELECT created_at
            FROM links_to_collections

            WHERE collection_id = :collection_id
            AND created_at >= :since

            ORDER BY created_at ASC
            LIMIT 1
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection->id,
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
        ]);

        $published_at = $statement->fetchColumn();

        if (!is_string($published_at)) {
            return null;
        }

        $published_at = \DateTimeImmutable::createFromFormat(
            Database\Column::DATETIME_FORMAT,
            $published_at,
        );

        if ($published_at === false) {
            return null;
        }

        return $published_at;
    }

    /**
     * Return the list of url ids indexed by urls for the given collection.
     *
     * @return array<string, string>
     */
    public static function listUrlsToIdsByCollection(Collection $collection): array
    {
        $sql = <<<SQL
            SELECT l.url, l.id FROM links l, links_to_collections lc
            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection->id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Return the list of urls indexed by entry ids for the given collection.
     *
     * @return array<string, array{
     *     'id': string,
     *     'url': string,
     * }>
     */
    public static function listEntryIdsToUrlsByCollection(Collection $collection): array
    {
        $sql = <<<SQL
            SELECT l.feed_entry_id, l.id, l.url
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 200
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection->id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_UNIQUE);
    }

    /**
     * Mark the relevant links to be grouped by sources in the given collection.
     *
     * Links are grouped if there are several links in the given collection
     * corresponding to the same source and the same day.
     *
     * The passed collection_id must correspond to a "news" collection. For
     * now, it's passed this way to improve performance and to simplify a bit
     * the SQL request.
     */
    public static function groupLinksBySources(Collection $collection): bool
    {
        $sql = <<<SQL
            UPDATE links
            SET group_by_source = true
            WHERE links.id IN (
                -- Create a "temporary table" to select the available sources
                -- from the given collection (e.g. sources that are referenced
                -- by more than 1 link).
                WITH sources AS (
                    SELECT date_trunc('day', slc.created_at) AS published_day,
                           sl.source_id
                    FROM links sl, links_to_collections slc

                    WHERE sl.id = slc.link_id
                    AND slc.collection_id = :collection_id

                    GROUP BY published_day, sl.source_id
                    HAVING COUNT(sl.id) > 1
                )

                -- Select the ids of links which have a source corresponding to
                -- one of the selected sources.
                SELECT l.id
                FROM links l, links_to_collections lc, sources s

                WHERE l.id = lc.link_id
                AND lc.collection_id = :collection_id

                AND l.source_id = s.source_id
                AND date_trunc('day', lc.created_at) = s.published_day
            );
        SQL;

        $parameters = [
            ':collection_id' => $collection->id,
        ];

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }
}
