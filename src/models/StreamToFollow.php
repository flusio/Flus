<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'streams_to_follows')]
class StreamToFollow
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $stream_id;

    #[Database\Column]
    public int $follow_id;

    public static function find(Stream $stream, Collection $source): ?self
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

    public static function findOrCreate(Stream $stream, Collection $source): self
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
     * @param Collection[] $sources
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

    /**
     * Attach the follow to the given streams, and detach it from the others.
     *
     * @param Stream[] $streams
     */
    public static function setStreams(FollowedCollection $follow, array $streams): void
    {
        // Make sure that the ids are unique to avoid trying to insert the same
        // values twice.
        $stream_ids = array_column($streams, 'id');
        $stream_ids = array_unique($stream_ids);
        $stream_ids = array_values($stream_ids);

        $database = Database::get();
        $database->beginTransaction();

        if ($stream_ids) {
            // First, delete all the streams_to_follow which aren't in the
            // given list of ids.
            $ids_as_question_marks = array_fill(0, count($stream_ids), '?');
            $ids_as_question_marks = implode(', ', $ids_as_question_marks);

            $sql = <<<SQL
                DELETE FROM streams_to_follows
                WHERE follow_id = ?
                AND stream_id NOT IN ({$ids_as_question_marks})
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute([$follow->id, ...$stream_ids]);

            // Then, insert the ids in the database. The unique index on
            // (stream_id, follow_id) allows to insert the streams without
            // checking first which ones are already attached.
            $created_at = \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT);
            $values_as_question_marks = [];
            $values = [];

            foreach ($stream_ids as $stream_id) {
                $values_as_question_marks[] = '(?, ?, ?)';
                $values = array_merge($values, [$created_at, $stream_id, $follow->id]);
            }

            $values_placeholder = implode(', ', $values_as_question_marks);

            $sql = <<<SQL
                INSERT INTO streams_to_follows (created_at, stream_id, follow_id)
                VALUES {$values_placeholder}
                ON CONFLICT DO NOTHING
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute($values);
        } else {
            // No ids? Then just delete all the rows associated to the follow.
            $sql = <<<SQL
                DELETE FROM streams_to_follows
                WHERE follow_id = ?
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute([$follow->id]);
        }

        $database->commit();
    }
}
