<?php

namespace flusio\models\dao\links;

/**
 * Add methods providing SQL queries specific to the DataExporter.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait DataExporterQueries
{
    /**
     * Return links of the given user which have at least one comment.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listByUserIdWithComments($user_id)
    {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, messages m

            WHERE l.id = m.link_id
            AND l.user_id = :user_id

            ORDER BY l.created_at DESC, l.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }
}
