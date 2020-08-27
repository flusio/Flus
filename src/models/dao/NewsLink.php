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
    use SaveHelper;

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
            SELECT
                nl.*,
                SUM(CASE c.type WHEN 'collection' THEN 1 END) url_collections_count,
                SUM(CASE c.type WHEN 'bookmarks' THEN 1 END) url_in_bookmarks

            FROM news_links nl
            LEFT JOIN links l ON nl.url = l.url AND l.user_id = :user_id
            LEFT JOIN links_to_collections lc ON lc.link_id = l.id
            LEFT JOIN collections c ON lc.collection_id = c.id AND c.user_id = :user_id

            WHERE nl.is_hidden = false
            AND nl.user_id = :user_id

            GROUP BY nl.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }
}
