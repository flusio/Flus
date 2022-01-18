<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait BulkDaoConnector
{
    /**
     * Bulk insert the models in database.
     *
     * This method expects that all the models are of the same type. Models are
     * exported with the toValues method which implies that the model should be
     * fully valid. For instance, the id and created_at properties must be set
     * before (or bulkInsert will try to insert null values and fail).
     *
     * It must be used with the DaoConnector.
     *
     * @see \flusio\models\DaoConnector
     * @see \flusio\models\dao\BulkQueries
     *
     * @param \Minz\Model[] $models
     *
     * @throws \PDOException if an error occurs during the insertion
     *
     * @return boolean True on success
     */
    public static function bulkInsert($models)
    {
        if (empty($models)) {
            return true;
        }

        $models_columns = [];
        $models_to_create = [];
        foreach ($models as $model) {
            $db_model = $model->toValues();
            $models_to_create = array_merge(
                $models_to_create,
                array_values($db_model)
            );

            if (!$models_columns) {
                $models_columns = array_keys($db_model);
            }
        }

        return self::daoCall('bulkInsert', $models_columns, $models_to_create);
    }
}
