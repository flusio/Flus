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
        $values = $model->toValues();
        if ($model->created_at) {
            $primary_key_name = $this->primary_key_name;
            $this->update($model->$primary_key_name, $values);
            return $model->$primary_key_name;
        } else {
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
            return $this->create($values);
        }
    }
}
