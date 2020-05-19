<?php

namespace flusio\models\dao;

/**
 * Represent a user of flusio in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class User extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\User::PROPERTIES);
        parent::__construct('users', 'id', $properties);
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
            $this->update($model->id, $values);
            return $model->id;
        } else {
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
            return $this->create($values);
        }
    }

    /**
     * Return not validated users older than the given time.
     *
     * @see \Minz\Time::ago
     *
     * @param integer $number
     * @param string $unit
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured during the execution
     *
     * @return array
     */
    public function listNotValidatedOlderThan($number, $unit)
    {
        $date = \Minz\Time::ago($number, $unit)->format(\Minz\Model::DATETIME_FORMAT);
        $sql = "SELECT * FROM users WHERE validated_at IS NULL AND created_at <= ?";
        $statement = $this->prepare($sql);
        $result = $statement->execute([$date]);
        if (!$result) {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }

        $result = $statement->fetchAll();
        if ($result !== false) {
            return $result;
        } else {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }
    }
}
