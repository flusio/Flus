<?php

namespace App\services;

use Minz\Database;

/**
 * This service allows to acquire locks that can be shared across different
 * threads.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'locks', primary_key: 'key')]
class Lock
{
    use Database\Recordable;

    #[Database\Column]
    public string $key;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public \DateTimeImmutable $expired_at;

    /**
     * Acquire a lock for a given time.
     *
     * If $expired_at is not given, the lock is acquired for 1 hour.
     */
    public static function acquire(string $key, ?\DateTimeImmutable $expired_at = null): ?self
    {
        self::deleteIfExpired($key);

        $lock = new Lock();
        $lock->key = $key;
        $lock->created_at = \Minz\Time::now();
        if ($expired_at) {
            $lock->expired_at = $expired_at;
        } else {
            $lock->expired_at = \Minz\Time::fromNow(1, 'hour');
        }

        $sql = <<<SQL
            INSERT INTO locks (key, created_at, expired_at)
            VALUES (:key, :created_at, :expired_at)
            ON CONFLICT DO NOTHING
            RETURNING key
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);

        $result = $statement->execute([
            ':key' => $lock->key,
            ':created_at' => $lock->created_at->format(Database\Column::DATETIME_FORMAT),
            ':expired_at' => $lock->expired_at->format(Database\Column::DATETIME_FORMAT),
        ]);

        $returned_key = $statement->fetchColumn();

        if ($returned_key !== false) {
            $lock->is_persisted = true;
            return $lock;
        } else {
            return null;
        }
    }

    /**
     * Delete a lock by its key if any and if it has expired.
     */
    private static function deleteIfExpired(string $key): bool
    {
        $now = \Minz\Time::now();

        $sql = <<<SQL
            DELETE FROM locks
            WHERE key = :key
            AND expired_at <= :now
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);

        return $statement->execute([
            ':key' => $key,
            ':now' => $now->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Release (= delete) the current lock.
     */
    public function release(): bool
    {
        return $this->remove();
    }
}
