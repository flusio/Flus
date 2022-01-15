<?php

namespace flusio\models\dao;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait BulkQueries
{
    /**
     * Insert in DB all the given objects.
     *
     * No validation are done on this insert, you must be sure they are valid
     * values. Rows are not inserted on conflict, but no exceptions are raised
     * either.
     *
     * @param string[] $columns
     * @param array $values
     *
     * @return boolean True on success
     */
    public function bulkInsert($columns, $values)
    {
        $number_rows = count($values) / count($columns);
        $row_as_question_marks = array_fill(0, count($columns), '?');
        $row_placeholder = implode(', ', $row_as_question_marks);
        $rows_as_question_marks = array_fill(0, $number_rows, "({$row_placeholder})");
        $rows_placeholder = implode(", ", $rows_as_question_marks);
        $columns_placeholder = implode(", ", $columns);

        $sql = <<<SQL
            INSERT INTO {$this->table_name} ({$columns_placeholder})
            VALUES {$rows_placeholder}
            ON CONFLICT DO NOTHING;
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return true;
    }
}
