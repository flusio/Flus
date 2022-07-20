<?php

namespace flusio\models\dao;

/**
 * Add methods providing SQL queries specific to the media (i.e. image_filename).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait MediaQueries
{
    /**
     * Returns the list of image_filename starting with the given string.
     *
     * @param string $prefix
     *
     * @return string[]
     */
    public function listImageFilenamesStartingWith($prefix)
    {
        $sql = <<<SQL
            SELECT image_filename FROM {$this->table_name}
            WHERE image_filename LIKE ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            $prefix . '%',
        ]);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }
}
