<?php

namespace App\models\collections;

use App\models\Link;
use App\models\LinkToCollection;
use App\models\User;
use Minz\Database;

/**
 * Add the relation between a collection and the links that it contains.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasLinks
{
    #[Database\Column(computed: true)]
    public ?int $number_links = null;

    /**
     * Return links of the current collection.
     *
     * @see Link::listComputedByCollection
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'published_date'?: ?\DateTimeImmutable,
     *     'source'?: ?string,
     *     'hidden'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * @return Link[]
     */
    public function links(array $selected_computed_props = [], array $options = []): array
    {
        return Link::listComputedByCollection(
            $this,
            $selected_computed_props,
            $options
        );
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
     * Return whether the link is in the collection.
     */
    public function hasLink(Link $link): bool
    {
        return LinkToCollection::existsBy([
            'link_id' => $link->id,
            'collection_id' => $this->id,
        ]);
    }

    /**
     * Add the links to the collection.
     *
     * @param Link[] $links
     */
    public function addLinks(
        array $links,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $link_ids = array_column($links, 'id');
        LinkToCollection::attach($link_ids, [$this->id], $at);

        if ($sync_publication_frequency) {
            $this->syncPublicationFrequencyPerYear();
            $this->save();
        }
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
     * Return the sources of the given links, indexed by their ids.
     *
     * The links without any source, and the sources that don't exist anymore,
     * are absent from the returned array.
     *
     * @param Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self>
     */
    public static function listSourcesByLinks(array $links, int $chunk_size = 1000): array
    {
        $source_ids = array_column($links, 'source_id');
        $source_ids = array_filter($source_ids);
        $source_ids = array_unique($source_ids);
        $source_ids = array_values($source_ids);

        $sources_by_ids = [];

        foreach (array_chunk($source_ids, $chunk_size) as $chunk_source_ids) {
            $sources = self::listBy(['id' => $chunk_source_ids]);

            foreach ($sources as $source) {
                $sources_by_ids[$source->id] = $source;
            }
        }

        return $sources_by_ids;
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
}
