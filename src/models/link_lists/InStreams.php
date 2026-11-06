<?php

namespace App\models\link_lists;

use App\auth;
use App\models\Source;
use App\models\Stream;
use App\models\StreamToFollow;
use App\models\User;
use Minz\Database;

/**
 * Add the relation between a source and the streams that contain it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InStreams
{
    /**
     * Return the number of streams of the given user in which this source
     * is a source.
     */
    public function countStreamsByUser(User $user): int
    {
        return $this->memoize("count_streams_{$user->id}", function () use ($user): int {
            $counts = StreamToFollow::countByUserAndSources($user, [$this]);
            return $counts[$this->id] ?? 0;
        });
    }

    /**
     * Set the number of streams of a user without querying the database.
     *
     * @see Preloader
     */
    public function preloadCountStreamsByUser(User $user, int $count): void
    {
        $this->memoizeValue("count_streams_{$user->id}", $count);
    }

    /**
     * List the sources present in the given stream.
     *
     * Only the sources that the context user can view are returned, or
     * only the public ones if no context user is given.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return self[]
     */
    public static function listByStream(Stream $stream, array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;

        [$visibility_clause, $visibility_parameters] = auth\LinkListsAccess::viewQuery($context_user, 'c');

        $parameters = array_merge([
            ':stream_id' => $stream->id,
        ], $visibility_parameters);

        $sql = <<<SQL
            SELECT c.*
            FROM collections c, followed_collections fc, streams_to_follows sf

            WHERE c.id = fc.collection_id
            AND sf.follow_id = fc.id
            AND sf.stream_id = :stream_id
            AND {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return Source::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the sources of the given streams, indexed by stream id.
     *
     * Only the sources that the given user can view are returned. The streams
     * without viewable source are absent from the result.
     *
     * @param Stream[] $streams
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return array<string, self[]>
     */
    public static function listByStreams(array $streams, array $options): array
    {
        if (!$streams) {
            return [];
        }

        $user = $options['context_user'] ?? null;

        $parameters = [];

        $stream_ids_placeholders = [];

        foreach (array_values($streams) as $index => $stream) {
            $placeholder = ":stream_id_{$index}";
            $stream_ids_placeholders[] = $placeholder;
            $parameters[$placeholder] = $stream->id;
        }

        /** @var literal-string */
        $stream_ids_statement = implode(', ', $stream_ids_placeholders);

        [$visibility_clause, $visibility_parameters] = auth\LinkListsAccess::viewQuery($user, 'c');
        $parameters = array_merge($parameters, $visibility_parameters);

        $sql = <<<SQL
            SELECT c.*, sf.stream_id
            FROM collections c, followed_collections fc, streams_to_follows sf

            WHERE c.id = fc.collection_id
            AND sf.follow_id = fc.id
            AND sf.stream_id IN ({$stream_ids_statement})
            AND {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $sources_by_stream_ids = [];

        foreach ($statement->fetchAll() as $row) {
            $stream_id = strval($row['stream_id']);
            unset($row['stream_id']);

            $sources_by_stream_ids[$stream_id][] = Source::fromDatabaseRow($row);
        }

        return $sources_by_stream_ids;
    }
}
