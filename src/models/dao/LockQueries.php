<?php

namespace flusio\models\dao;

/**
 * Add methods providing SQL queries specific to the lock system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LockQueries
{
    /**
     * Lock a resource (it must have a locked_at property).
     *
     * @param string $id
     *
     * @return boolean True if the lock is successful, false otherwise
     */
    public function lock($id)
    {
        $sql = <<<SQL
            UPDATE {$this->table_name}
            SET locked_at = :locked_at
            WHERE id = :id
            AND (locked_at IS NULL OR locked_at <= :lock_timeout)
        SQL;

        $now = \Minz\Time::now();
        $lock_timeout = \Minz\Time::ago(1, 'hour');
        $statement = $this->prepare($sql);
        $statement->execute([
            ':locked_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            ':id' => $id,
            ':lock_timeout' => $lock_timeout->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        return $statement->rowCount() === 1;
    }

    /**
     * Unlock a resource (it must have a locked_at property).
     *
     * @param string $id
     *
     * @return boolean True if the unlock is successful, false otherwise
     */
    public function unlock($id)
    {
        $sql = <<<SQL
            UPDATE {$this->table_name}
            SET locked_at = null
            WHERE id = :id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':id' => $id,
        ]);
        return $statement->rowCount() === 1;
    }
}
