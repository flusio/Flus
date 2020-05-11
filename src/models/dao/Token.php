<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Token extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Token::PROPERTIES);
        parent::__construct('tokens', 'token', $properties);
    }

    /**
     * Create or update a model in database
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
            $this->update($model->token, $values);
            return $model->token;
        } else {
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
            return $this->create($values);
        }
    }
}
