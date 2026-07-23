<?php

namespace App\models\dao;

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
     * values.
     *
     * By default, rows are not inserted (silently) on conflict. You can change
     * this by overriding the bulkInsertOnConflict method.
     *
     * The objects are inserted by chunks of $chunk_size rows, to keep the
     * number of parameters of the prepared statements under the PostgreSQL
     * limit (65535), even for tables with many columns.
     *
     * @param object[] $models
     * @param positive-int $chunk_size
     *
     * @throws \PDOException if an error occurs during the insertion
     */
    public static function bulkInsert(array $models, int $chunk_size = 500): bool
    {
        foreach (array_chunk($models, $chunk_size) as $chunk) {
            self::bulkInsertChunk($chunk);
        }

        return true;
    }

    /**
     * Insert in DB the given chunk of objects.
     *
     * @param object[] $models
     *
     * @throws \PDOException if an error occurs during the insertion
     */
    private static function bulkInsertChunk(array $models): void
    {
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

        if (!$models_columns) {
            // nothing to insert
            return;
        }

        $number_rows = count($models_values) / count($models_columns);

        assert(is_int($number_rows));

        $row_as_question_marks = array_fill(0, count($models_columns), '?');
        $row_placeholder = implode(', ', $row_as_question_marks);
        $rows_as_question_marks = array_fill(0, $number_rows, "({$row_placeholder})");
        $rows_placeholder = implode(", ", $rows_as_question_marks);
        $columns_placeholder = implode(", ", $models_columns);

        $table_name = self::tableName();

        $on_conflict = self::bulkInsertOnConflict();

        $sql = <<<SQL
            INSERT INTO {$table_name} ({$columns_placeholder})
            VALUES {$rows_placeholder}
            {$on_conflict};
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($models_values);
    }

    /**
     * Return the behaviour to have on conflict during a bulk insert.
     *
     * It returns 'ON CONFLICT DO NOTHING' by default.
     *
     * @return literal-string
     */
    public static function bulkInsertOnConflict(): string
    {
        return 'ON CONFLICT DO NOTHING';
    }
}
