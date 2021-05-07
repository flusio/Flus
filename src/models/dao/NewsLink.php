<?php

namespace flusio\models\dao;

/**
 * Represent a link (displayed in news) in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLink extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\NewsLink::PROPERTIES);
        parent::__construct('news_links', 'id', $properties);
    }

    /**
     * Return computed DB NewsLinks for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listComputedByUserId($user_id)
    {
        $sql = <<<'SQL'
            SELECT nl.*, (
                SELECT COUNT(*) FROM messages m
                WHERE m.link_id = nl.link_id
            ) AS number_comments
            FROM news_links nl

            WHERE nl.is_removed = false
            AND nl.is_read = false
            AND nl.user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }
}
