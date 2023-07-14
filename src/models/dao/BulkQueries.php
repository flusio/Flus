<?php

namespace flusio\models\dao;

use Minz\Database;

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
     * values. Rows are not inserted (silently) on conflict.
     *
     * @param object[] $models
     *
     * @throws \PDOException if an error occurs during the insertion
     */
    public static function bulkInsert(array $models): bool
    {
        if (empty($models)) {
            // nothing to insert
            return true;
        }

        $models_columns = [];
        $models_values = [];
        foreach ($models as $model) {
            if (!is_callable([$model, 'toDbValues'])) {
                continue;
            }

            $model_values = $model->toDbValues();

            $models_values = array_merge(
                $models_values,
                array_values($model_values)
            );

            if (!$models_columns) {
                $models_columns = array_keys($model_values);
            }
        }

        $number_rows = count($models_values) / count($models_columns);

        assert(is_int($number_rows));

        $row_as_question_marks = array_fill(0, count($models_columns), '?');
        $row_placeholder = implode(', ', $row_as_question_marks);
        $rows_as_question_marks = array_fill(0, $number_rows, "({$row_placeholder})");
        $rows_placeholder = implode(", ", $rows_as_question_marks);
        $columns_placeholder = implode(", ", $models_columns);

        $table_name = self::tableName();

        $sql = <<<SQL
            INSERT INTO {$table_name} ({$columns_placeholder})
            VALUES {$rows_placeholder}
            ON CONFLICT DO NOTHING;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($models_values);

        return true;
    }
}
