<?php

namespace flusio\models\dao\links;

/**
 * Add methods providing SQL queries specific to the lock system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LockQueries
{
    /**
     * Lock a link
     *
     * @param string $link_id
     *
     * @return boolean True if the lock is successful, false otherwise
     */
    public function lock($link_id)
    {
        $sql = <<<SQL
            UPDATE links
            SET locked_at = :locked_at
            WHERE id = :link_id
            AND (locked_at IS NULL OR locked_at <= :lock_timeout)
        SQL;

        $now = \Minz\Time::now();
        $lock_timeout = \Minz\Time::ago(1, 'hour');
        $statement = $this->prepare($sql);
        $statement->execute([
            ':locked_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            ':link_id' => $link_id,
            ':lock_timeout' => $lock_timeout->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        return $statement->rowCount() === 1;
    }

    /**
     * Unlock a link
     *
     * @param string $link_id
     *
     * @return boolean True if the unlock is successful, false otherwise
     */
    public function unlock($link_id)
    {
        $sql = <<<SQL
            UPDATE links
            SET locked_at = null
            WHERE id = :link_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':link_id' => $link_id,
        ]);
        return $statement->rowCount() === 1;
    }
}
