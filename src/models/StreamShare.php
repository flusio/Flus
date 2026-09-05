<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'stream_shares')]
class StreamShare
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $stream_id;

    // used to sort stream shares easily
    #[Database\Column(computed: true)]
    public ?string $username;

    public function __construct(User $user, Stream $stream)
    {
        $this->user_id = $user->id;
        $this->stream_id = $stream->id;
    }

    /**
     * Return the user the stream is shared with.
     */
    public function user(): User
    {
        return User::require($this->user_id);
    }

    /**
     * Return the shares of the given stream, with their computed username.
     *
     * @return self[]
     */
    public static function listByStream(Stream $stream): array
    {
        $sql = <<<SQL
            SELECT ss.*, u.username
            FROM stream_shares ss

            INNER JOIN users u
            ON u.id = ss.user_id

            WHERE ss.stream_id = :stream_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            'stream_id' => $stream->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
