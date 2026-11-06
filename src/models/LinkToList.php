<?php

namespace App\models;

use Minz\Database;

/**
 * Connect links to the lists containing them (collections, feeds and
 * journals).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links_to_collections')]
class LinkToList
{
    use dao\BulkQueries;
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $collection_id;

    public function __construct(Link $link, LinkList $link_list)
    {
        $this->link_id = $link->id;
        $this->collection_id = $link_list->id;
    }

    /**
     * Attach the lists to the given links.
     *
     * The links already attached to a list keep their attachment date.
     *
     * @param Link[] $links
     * @param LinkList[] $link_lists
     * @param positive-int $chunk_size
     */
    public static function attach(
        array $links,
        array $link_lists,
        ?\DateTimeImmutable $created_at = null,
        int $chunk_size = 500,
    ): bool {
        if (!$links || !$link_lists) {
            // nothing to insert
            return true;
        }

        if (!$created_at) {
            $created_at = \Minz\Time::now();
        }

        $pairs = self::pairs($links, $link_lists);

        foreach (array_chunk($pairs, $chunk_size) as $chunk_pairs) {
            $values_as_question_marks = [];
            $values = [];
            foreach ($chunk_pairs as [$link_id, $collection_id]) {
                $values_as_question_marks[] = '(?, ?, ?)';
                $values = array_merge($values, [
                    $created_at->format(Database\Column::DATETIME_FORMAT),
                    $link_id,
                    $collection_id,
                ]);
            }
            $values_placeholder = implode(", ", $values_as_question_marks);

            $sql = <<<SQL
                INSERT INTO links_to_collections (created_at, link_id, collection_id)
                VALUES {$values_placeholder}
                ON CONFLICT DO NOTHING;
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($values);
        }

        return true;
    }

    /**
     * Detach the lists from the given links.
     *
     * @param Link[] $links
     * @param LinkList[] $link_lists
     * @param positive-int $chunk_size
     */
    public static function detach(array $links, array $link_lists, int $chunk_size = 500): bool
    {
        if (!$links || !$link_lists) {
            // nothing to delete
            return true;
        }

        $pairs = self::pairs($links, $link_lists);

        $result = true;

        foreach (array_chunk($pairs, $chunk_size) as $chunk_pairs) {
            $values_as_question_marks = [];
            $values = [];
            foreach ($chunk_pairs as [$link_id, $collection_id]) {
                $values_as_question_marks[] = '(link_id = ? AND collection_id = ?)';
                $values = array_merge($values, [$link_id, $collection_id]);
            }
            $values_placeholder = implode(' OR ', $values_as_question_marks);

            $sql = <<<SQL
                DELETE FROM links_to_collections
                WHERE {$values_placeholder};
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $result = $statement->execute($values) && $result;
        }

        return $result;
    }

    /**
     * Return the cartesian product of the link and list ids.
     *
     * @param Link[] $links
     * @param LinkList[] $link_lists
     *
     * @return array<array{string, string}>
     */
    private static function pairs(array $links, array $link_lists): array
    {
        $pairs = [];

        foreach ($links as $link) {
            foreach ($link_lists as $link_list) {
                $pairs[] = [$link->id, $link_list->id];
            }
        }

        return $pairs;
    }
}
