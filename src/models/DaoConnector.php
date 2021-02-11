<?php

namespace flusio\models;

use flusio\utils;

/**
 * The DaoConnector is a magic trait which brings database methods directly in
 * models.
 *
 * It is based on the basic assumption that a model class (under flusio\models)
 * using this trait has a corresponding DatabaseModel under flusio\models\dao.
 * If so, it creates a static dao attribute and transfer the calls to it.
 *
 * The list and find results are converted from arrays to models. For custom
 * DAO methods, you can use the `daoToModel`, `daoToList` or `daoCall` to
 * transfer the calls to the DatabaseModel.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait DaoConnector
{
    /** @var \Minz\DatabaseModel */
    private static $dao;

    /**
     * Create or update the current model in database.
     *
     * It also sets the `created_at` property at creation. If created, the
     * primary key and created_at properties are set in the model.
     *
     * @throws \Minz\DatabaseModelError
     */
    public function save()
    {
        $dao = self::dao();
        $primary_key_name = $dao->primaryKeyName();
        $values = $this->toValues();
        if ($this->created_at) {
            self::update($this->$primary_key_name, $values);
        } else {
            $values['created_at'] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);

            // If the value is null, it most probably means the id is a serial
            // type (i.e. it will be set by the DB). However, if we pass the
            // null value, postgresql will try to create an entry with
            // id=null, which will fail. So we need to remove null values.
            if ($values[$primary_key_name] === null) {
                unset($values[$primary_key_name]);
            }

            $pk_value = self::create($values);

            $this->$primary_key_name = $pk_value;
            $this->created_at = $values['created_at'];
        }
    }

    /**
     * @see \Minz\DatabaseModel::create
     */
    public static function create($values)
    {
        return self::daoCall('create', $values);
    }

    /**
     * @see \Minz\DatabaseModel::update
     */
    public static function update($primary_key, $values)
    {
        return self::daoCall('update', $primary_key, $values);
    }

    /**
     * @see \Minz\DatabaseModel::delete
     */
    public static function delete($pk_values)
    {
        return self::daoCall('delete', $pk_values);
    }

    /**
     * @see \Minz\DatabaseModel::count
     */
    public static function count()
    {
        return self::daoCall('count');
    }

    /**
     * @see \Minz\DatabaseModel::exists
     */
    public static function exists($primary_keys)
    {
        return self::daoCall('exists', $primary_keys);
    }

    /**
     * @see \Minz\DatabaseModel::find
     *
     * @return \Minz\Model|null
     */
    public static function find($values)
    {
        return self::daoToModel('find', $values);
    }

    /**
     * @see \Minz\DatabaseModel::findBy
     *
     * @return \Minz\Model|null
     */
    public static function findBy($values)
    {
        return self::daoToModel('findBy', $values);
    }

    /**
     * @see \Minz\DatabaseModel::listAll
     *
     * @return \Minz\Model[]
     */
    public static function listAll()
    {
        return self::daoToList('listAll');
    }

    /**
     * @see \Minz\DatabaseModel::listBy
     *
     * @return \Minz\Model[]
     */
    public static function listBy($values)
    {
        return self::daoToList('listBy', $values);
    }

    /**
     * Wrapper around listAll to return an specific item, useful during tests.
     *
     * @param integer $index
     *
     * @return \Minz\Model|null
     */
    public static function take($index = 0)
    {
        $models = self::daoToList('listAll');
        if ($models) {
            return $models[$index];
        } else {
            return null;
        }
    }

    /**
     * Transfer a method call to the dao and transform its result in a model
     *
     * @param string $name The method name to call
     * @param mixed $args,... Arguments to pass to the method
     *
     * @throws \BadMethodCallException if the method cannot be called
     *
     * @return \Minz\Model|null
     */
    public static function daoToModel($name, ...$arguments)
    {
        $db_model = self::daoCall($name, ...$arguments);
        if ($db_model) {
            return new self($db_model);
        } else {
            return null;
        }
    }

    /**
     * Transfer a method call to the dao and transform its result in a list of models
     *
     * @param string $name The method name to call
     * @param mixed $args,... Arguments to pass to the method
     *
     * @throws \BadMethodCallException if the method cannot be called
     *
     * @return \Minz\Model[]
     */
    public static function daoToList($name, ...$arguments)
    {
        $db_models = self::daoCall($name, ...$arguments);
        $models = [];
        foreach ($db_models as $db_model) {
            $models[] = new self($db_model);
        }
        return $models;
    }

    /**
     * Transfer a method call to the dao
     *
     * @param string $name The method name to call
     * @param mixed $args,... Arguments to pass to the method
     *
     * @throws \BadMethodCallException if the method cannot be called
     *
     * @return mixed
     */
    public static function daoCall($name, ...$arguments)
    {
        $dao = self::dao();

        if (!is_callable([$dao, $name])) {
            throw new \BadMethodCallException('Call to undefined method ' . get_called_class() . '::' . $name);
        }

        return $dao->$name(...$arguments);
    }

    /**
     * Return the dao for the current model. It stores the dao in a static
     * attribute in order to not instantiate it at each call.
     *
     * @return \Minz\DatabaseModel
     */
    private static function dao()
    {
        if (!self::$dao) {
            $base_class = substr(get_called_class(), strlen('flusio\\models\\'));
            $dao_class_name = "\\flusio\\models\\dao\\{$base_class}";
            self::$dao = new $dao_class_name();
        }
        return self::$dao;
    }
}
