<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Token extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Token::PROPERTIES);
        parent::__construct('tokens', 'token', $properties);
    }

    /**
     * Delete tokens that have expired.
     *
     * @return boolean True on success
     */
    public function deleteExpired()
    {
        $sql = <<<SQL
            DELETE FROM tokens
            WHERE expired_at <= ?
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }
}
