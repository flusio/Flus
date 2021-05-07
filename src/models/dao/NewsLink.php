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
     * Return current NewsLinks for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listCurrentNews($user_id)
    {
        $sql = <<<'SQL'
            SELECT * FROM news_links
            WHERE is_removed = false
            AND is_read = false
            AND user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }
}
