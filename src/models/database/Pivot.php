<?php

namespace App\models\database;

use Minz\Database;
use Minz\Errors;

/**
 * Manage a pivot table joining two other tables.
 *
 * The model must declare the two columns referencing the joined tables with
 * the pivotColumns() method: first the column of the models, then the column
 * of the related models.
 *
 * @phpstan-type PivotModel object{id: string|int}
 *
 * @phpstan-import-type ModelValues from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Pivot
{
    /**
     * Return the names of the two columns referencing the joined tables: the
     * column of the models, then the column of the related models.
     *
     * @return array{literal-string, literal-string}
     */
    abstract public static function pivotColumns(): array;

    /**
     * Attach the related models to the models.
     *
     * The pairs that are already attached are kept as they are (i.e. with their
     * values).
     *
     * The values are inserted in the other columns of the table, with the same
     * value for every row. If the model declares a created_at column
     * of DateTimeImmutable type, it is set to the current time unless it is
     * given in the values.
     *
     * @param PivotModel[] $models
     * @param PivotModel[] $related_models
     * @param ModelValues $values
     * @param positive-int $chunk_size
     *
     * @throws Errors\DatabaseModelError
     *     If the values include invalid parameters (undefined or computed
     *     column, or bad format).
     */
    public static function attach(
        array $models,
        array $related_models,
        array $values = [],
        int $chunk_size = 500,
    ): void {
        if (!$models || !$related_models) {
            // nothing to insert
            return;
        }

        $columns = static::pivotColumns();
        $table_name = static::tableName();

        $database_columns = self::databaseColumns(include_computed: false);

        if (
            isset($database_columns['created_at']) &&
            $database_columns['created_at']['type'] === 'DateTimeImmutable'
        ) {
            $values['created_at'] ??= \Minz\Time::now();
        }

        $values = Database\Helper::convertValuesToDatabase(static::class, $values);

        $columns = array_merge($columns, array_keys($values));
        $row_values = array_values($values);

        $columns_statement = implode(', ', $columns);
        $row_placeholder = array_fill(0, count($columns), '?');
        $row_placeholder = '(' . implode(', ', $row_placeholder) . ')';

        $pairs = self::pivotPairs($models, $related_models);

        $database = \Minz\Database::get();

        foreach (array_chunk($pairs, $chunk_size) as $chunk_pairs) {
            $rows_placeholders = [];
            $statement_values = [];

            foreach ($chunk_pairs as [$model_id, $related_id]) {
                $rows_placeholders[] = $row_placeholder;

                $statement_values[] = $model_id;
                $statement_values[] = $related_id;

                foreach ($row_values as $value) {
                    $statement_values[] = $value;
                }
            }

            $rows_statement = implode(', ', $rows_placeholders);

            $sql = <<<SQL
                INSERT INTO {$table_name} ({$columns_statement})
                VALUES {$rows_statement}
                ON CONFLICT DO NOTHING
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute($statement_values);
        }
    }

    /**
     * Detach the related models from the models.
     *
     * @param PivotModel[] $models
     * @param PivotModel[] $related_models
     * @param positive-int $chunk_size
     */
    public static function detach(array $models, array $related_models, int $chunk_size = 500): void
    {
        if (!$models || !$related_models) {
            // nothing to delete
            return;
        }

        [$model_column, $related_column] = static::pivotColumns();
        $table_name = static::tableName();

        $pairs = self::pivotPairs($models, $related_models);

        $database = \Minz\Database::get();

        foreach (array_chunk($pairs, $chunk_size) as $chunk_pairs) {
            $conditions = [];
            $values = [];

            foreach ($chunk_pairs as [$model_id, $related_id]) {
                $conditions[] = "({$model_column} = ? AND {$related_column} = ?)";
                $values[] = $model_id;
                $values[] = $related_id;
            }

            $conditions_statement = implode(' OR ', $conditions);

            $sql = <<<SQL
                DELETE FROM {$table_name}
                WHERE {$conditions_statement}
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute($values);
        }
    }

    /**
     * Attach the related models to the model, and detach the previous ones
     * that are not part of the related models anymore.
     *
     * The values are inserted in the other columns of the attached rows (see
     * attach()).
     *
     * It returns the attached and the detached related models.
     *
     * @template TRelated of object{id: string|int}
     *
     * @param PivotModel $model
     * @param TRelated[] $related_models
     * @param TRelated[] $existing_related_models
     * @param ModelValues $values
     *
     * @return array{TRelated[], TRelated[]}
     *
     * @throws Errors\DatabaseModelError
     *     If the values include invalid parameters (undefined or computed
     *     column, or bad format).
     */
    public static function set(
        object $model,
        array $related_models,
        array $existing_related_models,
        array $values = [],
    ): array {
        $related_models_by_ids = array_column($related_models, null, 'id');
        $previous_related_models_by_ids = array_column($existing_related_models, null, 'id');

        $models_to_attach = array_values(array_diff_key($related_models_by_ids, $previous_related_models_by_ids));
        $models_to_detach = array_values(array_diff_key($previous_related_models_by_ids, $related_models_by_ids));

        $database = \Minz\Database::get();
        $database->beginTransaction();

        try {
            self::attach([$model], $models_to_attach, $values);
            self::detach([$model], $models_to_detach);

            $database->commit();
        } catch (\Throwable $exception) {
            $database->rollBack();
            throw $exception;
        }

        return [$models_to_attach, $models_to_detach];
    }

    /**
     * Return the cartesian product of the ids of the given models.
     *
     * @param PivotModel[] $models
     * @param PivotModel[] $related_models
     *
     * @return array<array{string|int, string|int}>
     */
    private static function pivotPairs(array $models, array $related_models): array
    {
        $pairs = [];

        foreach ($models as $model) {
            foreach ($related_models as $related_model) {
                $pairs[] = [$model->id, $related_model->id];
            }
        }

        return $pairs;
    }
}
