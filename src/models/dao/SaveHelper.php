<?php

namespace flusio\models\dao;

/**
 * Provide a save() method, which can be used to create and update a
 * DatabaseModel. It should probably exist by default in DatabaseModel, but I
 * want to wait to see if a different pattern emerge.
 *
 * Note: it requires a `created_at` attribute to be used.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait SaveHelper
{
    /**
     * Create or update a model in database
     *
     * It also sets the `created_at` attribute at creation.
     *
     * @param \Minz\Model $model
     *
     * @throws \Minz\DatabaseModelError
     *
     * @return string The primary key value of the model
     */
    public function save($model)
    {
        $primary_key_name = $this->primary_key_name;
        $values = $model->toValues();
        if ($model->created_at) {
            $this->update($model->$primary_key_name, $values);
            return $model->$primary_key_name;
        } else {
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);

            // If the value is null, it most probably means the id is a serial
            // type (i.e. it will be set by the DB). However, if we pass the
            // null value, postgresql will try to create an entry with
            // id=null, which will fail. So we need to remove null values.
            if ($values[$primary_key_name] === null) {
                unset($values[$primary_key_name]);
            }

            return $this->create($values);
        }
    }
}
