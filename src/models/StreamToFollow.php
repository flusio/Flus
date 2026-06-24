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
}
