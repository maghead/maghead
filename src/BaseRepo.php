<?php
namespace LazyRecord;
use PDOException;
use PDOStatement;
use PDO;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use BadMethodCallException;
use ArrayIterator;
use Serializable;
use ArrayAccess;

use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\UpdateQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Universal\Query\InsertQuery;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Raw;

use LazyRecord\Connection;
use LazyRecord\Result\OperationError;
use LazyRecord\Schema\SchemaLoader;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship\Relationship;
use LazyRecord\Exception\MissingPrimaryKeyException;
use LazyRecord\Exception\QueryException;
use SerializerKit\XmlSerializer;
use ActionKit;
use Symfony\Component\Yaml\Yaml;

class BaseRepo
{
    protected $table;

    protected $alias;


    /**
     * @var Connection
     */
    protected $write;

    /**
     * @var Connection
     */
    protected $read;

    public function __construct(Connection $write, Connection $read = null)
    {
        $this->write = $write;
        $this->read = $read ? $read : $write;
    }

    public function getReadConnection()
    {
        return $this->read;
    }

    public function getWriteConnection()
    {
        return $this->write;
    }

    /**
     * We kept getTable() as dynamic that way we can change the table name.
     */
    public function getTable()
    {
        return $this->table ?: static::TABLE;
    }

    public function getAlias()
    {
        return $this->alias ?: static::TABLE_ALIAS;
    }

    /**
     * Find record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    // abstract static public function find($pkId);
    static protected function _stmFetch(PDOStatement $stm, array $args)
    {
        $stm->execute($args);
        $obj = $stm->fetch(PDO::FETCH_CLASS);
        $stm->closeCursor();
        return $obj;
    }

    /**
     * load method loads one record from the repository with compound conditions.
     *
     * @param array $args
     */
    public function load(array $args)
    {
        $schema = $this->getSchema();
        $query = new SelectQuery();
        $query->select('*');
        $query->from($this->getTable(), $this->getAlias());

        $conn = $this->read;
        $driver = $conn->getQueryDriver();
        $query->where($args);
        $arguments = new ArgumentArray();
        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, static::MODEL_CLASS);
        $stm->execute($arguments->toArray());
        return $stm->fetch(PDO::FETCH_CLASS);
    }

    public function loadForUpdate(array $args)
    {
        $schema = $this->getSchema();
        $query = new SelectQuery();
        $query->select('*');
        $query->from($this->getTable(), $this->getAlias());
        $query->forUpdate();

        $conn = $this->read;
        $driver = $conn->getQueryDriver();

        if (!$driver instanceof PDOMySQLDriver) {
            throw new Exception("The current driver doesn't support SELECT ... FOR UPDATE");
        }

        $query->where($args);
        $arguments = new ArgumentArray();
        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, static::MODEL_CLASS);
        $stm->execute($arguments->toArray());
        return $stm->fetch(PDO::FETCH_CLASS);
    }

    public function updateOrCreate(array $args, $byKeys = null)
    {
        $primaryKey = static::PRIMARY_KEY;
        $record = null;
        if ($primaryKey && isset($args[$primaryKey])) {
            $val = $args[$primaryKey];
            $record = $this->find($val);
        } else if ($byKeys) {
            $conds = [];
            foreach ((array) $byKeys as $k) {
                if (array_key_exists($k, $args)) {
                    $conds[$k] = $args[$k];
                }
            }
            $record = $this->load($conds);
        }

        if ($record && $record->hasKey()) {
            $record->update($args);
            return $record;
        } else {
            return $this->create($args);
        }
    }

    public function loadByKeys(array $args, $byKeys = null)
    {
        $pk = static::PRIMARY_KEY;
        $record = null;
        if ($pk && isset($args[$pk])) {
            return $this->load([$pk => $args[$pk]]);
        } else if ($byKeys) {
            $conds = [];
            foreach ((array) $byKeys as $k) {
                if (array_key_exists($k, $args)) {
                    $conds[$k] = $args[$k];
                }
            }
            return $this->load($conds);
        }
        throw new MissingPrimaryKeyException('primary key is not defined.');
    }

    public function updateByPrimaryKey($kVal, array $args)
    {
        $schema = static::getSchema();

        // backup the arguments
        $origArgs = $args;
        $updateArgs = [];

        $conn = $this->write;
        $driver = $conn->getQueryDriver();

        $query = new UpdateQuery();

        $validationError = false;
        $validationResults = array();

        $args = $this->beforeUpdate($args);
        if ($args === false) {
            return Result::failure(_('Update failed'), array(
                    'args' => $args,
                ));
        }

        $record = $this->find($kVal);

        $arguments = new ArgumentArray();

        // foreach mixin schema, run their beforeUpdate method,
        $args = array_intersect_key($args, array_flip($schema->columnNames));

        foreach ($schema->columns as $n => $c) {
            if (isset($args[$n]) && !$args[$n] && !$c->primary) {
                if ($val = $c->getDefaultValue($record, $args)) {
                    $args[$n] = $val;
                }
            }

            // column validate (value is set.)
            if (!array_key_exists($n, $args)) {
                continue;
            }

            // if column is required (can not be empty) //   and default is defined.
            if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                return Result::failure("Value of $n is required.");
            }

            // TODO: Do not render immutable field in ActionKit
            // XXX: calling ::save() might update the immutable columns
            if ($c->immutable) {
                continue;
                // TODO: render as a validation results?
                // continue;
                // return Result::failure( "You can not update $n column, which is immutable.", array('args' => $args));
            }

            if ($args[$n] !== null && !is_array($args[$n]) && !$args[$n] instanceof Raw) {
                $args[$n] = $c->typeCast($args[$n]);
            }

            // The is_array function here is for checking raw sql value.
            if ($args[$n] !== null && !is_array($args[$n]) && !$args[$n] instanceof Raw) {
                if (false === $c->validateType($args[$n])) {
                    return Result::failure($args[$n].' is not '.$c->isa.' type');
                }
            }

            if ($c->filter || $c->canonicalizer) {
                $args[$n] = $c->canonicalizeValue($args[$n], $record, $args);
            }

            if ($validationResult = static::_validateColumn($c, $args[$n], $args, $record)) {
                $validationResults[$n] = $validationResult;
                if (!$validationResult['valid']) {
                    $validationError = true;
                }
            }

            // deflate the values into query
            /*
            if ($args[$n] instanceof Raw) {
                $updateArgs[$n] = $args[$n];
            } else {
                $updateArgs[$n] = $c->deflate($args[$n], $driver);
            }
            */

            // use parameter binding for binding
            $val = $args[$n];

            if (is_scalar($args[$n]) || is_null($args[$n])) {
                $updateArgs[$n] = $bind = new Bind($n, $driver->cast($args[$n]));
                $arguments->bind($bind);
            } elseif ($args[$n] instanceof Raw) {
                $updateArgs[$n] = $args[$n];
            } else {
                $updateArgs[$n] = $bind = new Bind($n, $c->deflate($args[$n], $driver));
                $arguments->bind($bind);
            }
        }

        if ($validationError) {
            return Result::failure('Validation failed.', array(
                    'validations' => $validationResults,
                ));
        }

        if (empty($updateArgs)) {
            return Result::failure('Empty arguments for update');
        }

        $query->set($updateArgs);
        $query->update($this->getTable());
        $query->where()->equal(static::PRIMARY_KEY, $kVal);

        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());
        $this->afterUpdate($origArgs);

        return Result::success('Updated successfully', array(
            'key' => $kVal,
            'sql' => $sql,
            'args' => $args,
            'type' => Result::TYPE_UPDATE,
        ));
    }





    // ================= TRIGGER METHODS ===================

    /**
     * Trigger method for "before creating new record".
     *
     * By overriding this method, you can modify the 
     * arguments that is passed to the query builder.
     *
     * Remember to return the arguments back.
     *
     * @param array $args Arguments
     *
     * @return array $args Arguments
     */
    public function beforeCreate(array $args)
    {
        return $args;
    }

    /**
     * Trigger for after creating new record.
     *
     * @param array $args
     */
    public function afterCreate(array $args)
    {
    }

    /**
     * Trigger method for delete
     */
    public function beforeDelete()
    {
    }

    public function afterDelete()
    {
    }

    /**
     * Trigger method for update
     */
    public function beforeUpdate(array $args)
    {
        return $args;
    }

    public function afterUpdate(array $args)
    {

    }




    // ============================= UTILITY METHODS =============================

    /**
     * Run validator to validate column.
     *
     * A validator could be:
     *   1. a ValidationKit validator,
     *   2. a closure
     *   3. a function name
     *
     * The validation result must be returned as in following format:
     *
     *   boolean (valid or invalid, true or false)
     *
     *   array( boolean valid , string message )
     *
     *   ValidationKit\ValidationMessage object.
     *
     * This method returns
     *
     *   (object) {
     *       valid: boolean valid or invalid
     *       field: string field name
     *       message: 
     *   }
     */
    static public function _validateColumn(RuntimeColumn $column, $val, array $args, $record)
    {
        // check for requried columns
        if ($column->required && ($val === '' || $val === null)) {
            return array(
                'valid' => false,
                'message' => sprintf(_('Field %s is required.'), $column->getLabel()),
                'field' => $column->name,
            );
        }

        // XXX: migrate this method to runtime column
        if ($validator = $column->validator) {
            if (is_callable($validator)) {
                $ret = call_user_func($validator, $val, $args, $record);
                if (is_bool($ret)) {
                    return array('valid' => $ret, 'message' => 'Validation failed.', 'field' => $column->name);
                } elseif (is_array($ret)) {
                    return array('valid' => $ret[0], 'message' => $ret[1], 'field' => $column->name);
                } else {
                    throw new Exception('Wrong validation result format, Please returns (valid,message) or (valid)');
                }
            } elseif (is_string($validator) && is_a($validator, 'ValidationKit\\Validator', true)) {
                // it's a ValidationKit\Validator
                $validator = $column->validatorArgs ? new $validator($column->get('validatorArgs')) : new $validator();
                $ret = $validator->validate($val);
                $msgs = $validator->getMessages();
                $msg = isset($msgs[0]) ? $msgs[0] : 'Validation failed.';

                return array('valid' => $ret, 'message' => $msg, 'field' => $column->name);
            } else {
                throw new Exception('Unsupported validator');
            }
        }
        if ($val && $column->validValues) {
            if ($validValues = $column->getValidValues($record, $args)) {
                // sort by index
                if (isset($validValues[0]) && !in_array($val, $validValues)) {
                    return array(
                        'valid' => false,
                        'message' => sprintf('%s is not a valid value for %s', $val, $column->name),
                        'field' => $column->name,
                    );
                }

                /*
                 * Validate for Options
                 * "Label" => "Value",
                 * "Group" => array( "Label" => "Value" )
                
                 * Order with key => value
                 *    value => label
                 */
                else {
                    $values = array_values($validValues);
                    foreach ($values as &$v) {
                        if (is_array($v)) {
                            $v = array_values($v);
                        }
                    }

                    if (!in_array($val, $values)) {
                        return array(
                            'valid' => false,
                            'message' => sprintf(_('%s is not a valid value for %s'), $val, $column->name),
                            'field' => $column->name,
                        );
                    }
                }
            }
        }
    }


}
