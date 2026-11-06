<?php

namespace App\models;

use App\models\database\Pivot;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'streams_to_follows')]
class StreamToFollow
{
    use dao\BulkQueries;
    use Database\Recordable;
    use Pivot;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $stream_id;

    #[Database\Column]
    public int $follow_id;

    public static function pivotColumns(): array
    {
        return ['follow_id', 'stream_id'];
    }

    public static function find(Stream $stream, Source $source): ?self
    {
        $sql = <<<SQL
            SELECT sf.*
            FROM streams_to_follows sf, followed_collections fc

            WHERE sf.follow_id = fc.id
            AND fc.user_id = :user_id
            AND fc.collection_id = :source_id
            AND sf.stream_id = :stream_id
        SQL;

        $parameters = [
            'user_id' => $stream->owner()->id,
            'source_id' => $source->id,
            'stream_id' => $stream->id,
        ];

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    public static function findOrCreate(Stream $stream, Source $source): self
    {
        $follow = FollowedCollection::findOrCreate($stream->owner(), $source);

        return self::findOrCreateBy([
            'stream_id' => $stream->id,
            'follow_id' => $follow->id,
        ]);
    }

    /**
     * Return the numbers of streams of the given user in which the given
     * sources are attached, indexed by the ids of these sources.
     *
     * The sources that are in no stream are absent from the returned array.
     *
     * @param Source[] $sources
     *
     * @return array<string, int>
     */
    public static function countByUserAndSources(User $user, array $sources): array
    {
        if (!$sources) {
            return [];
        }

        $source_ids = array_column($sources, 'id');
        $ids_as_question_marks = array_fill(0, count($source_ids), '?');
        $ids_as_question_marks = implode(', ', $ids_as_question_marks);

        $sql = <<<SQL
            SELECT fc.collection_id, COUNT(*) AS count
            FROM streams_to_follows sf, followed_collections fc

            WHERE sf.follow_id = fc.id
            AND fc.user_id = ?
            AND fc.collection_id IN ({$ids_as_question_marks})

            GROUP BY fc.collection_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$user->id, ...$source_ids]);

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[$row['collection_id']] = intval($row['count']);
        }

        return $counts;
    }
}
