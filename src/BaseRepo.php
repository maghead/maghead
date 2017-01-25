<?php
namespace Maghead;
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

use Maghead\Connection;
use Maghead\Result\OperationError;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\RuntimeColumn;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Exception\MissingPrimaryKeyException;
use Maghead\Exception\QueryException;
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


    protected $_preparedCreateStms = [];


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

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Find record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    static protected function _stmFetchOne(PDOStatement $stm, array $args)
    {
        $stm->execute($args);
        $obj = $stm->fetch(PDO::FETCH_CLASS);

        // PDOStatement::closeCursor() frees up the connection to the server so
        // that other SQL statements may be issued, but leaves the statement in
        // a state that enables it to be executed again.
        $stm->closeCursor();
        return $obj;
    }

    /**
     * Fetch all record.
     *
     * @param array condition array
     * @return BaseModel
     */
    // PHP 5.6 doesn't support static abstract
    static protected function _stmFetchAll(PDOStatement $stm, array $args)
    {
        $stm->execute($args);
        return $stm->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * load method loads one record from the repository with compound conditions.
     *
     * @param array $args
     */
    public function loadWith(array $args)
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

    public function loadByKeys(array $args, $byKeys = null)
    {
        $pk = static::PRIMARY_KEY;
        $record = null;
        if ($pk && isset($args[$pk])) {
            return $this->loadByPrimaryKey($args[$pk]);
        } else if ($byKeys) {
            $conds = [];
            foreach ((array) $byKeys as $k) {
                if (array_key_exists($k, $args)) {
                    $conds[$k] = $args[$k];
                }
            }
            return $this->loadWith($conds);
        }
        throw new MissingPrimaryKeyException('primary key is not defined.');
    }

    public function load($arg)
    {
        if (is_array($arg)) {
            return $this->loadWith($arg);
        }
        return $this->loadByPrimaryKey($arg);
    }

    public function rawUpdateByPrimaryKey($kVal, array $args)
    {
        $conn   = $this->write;
        $driver = $conn->getQueryDriver();
        $arguments = new ArgumentArray();
        $query = new UpdateQuery();
        $query->set($args);
        $query->update($this->getTable());
        $query->where()->equal(static::PRIMARY_KEY, $kVal);
        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());
        return Result::success('Updated', [
            'sql' => $sql,
            'type' => Result::TYPE_UPDATE,
        ]);
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
            return Result::failure('Update failed', [ 'args' => $args ]);
        }

        $record = $this->loadByPrimaryKey($kVal);

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



    /**
     * Method for creating new record, which is called from 
     * static::create and $record->create.
     *
     * 1. create method calls beforeCreate to 
     * trigger events or filter arguments.
     *
     * 2. it runs filterArrayWithColumns method to filter 
     * arguments with column definitions.
     *
     * 3. use currentUserCan method to check permission.
     *
     * 4. get column definitions and run filters, default value 
     *    builders, canonicalizer, type constraint checkers to build 
     *    a new arguments.
     *
     * 5. use these new arguments to build a SQL query with 
     *    SQLBuilder\QueryBuilder.
     *
     * 6. insert SQL into data source (write)
     *
     * 7. reutrn the operation result.
     *
     * @param array $args data
     *
     * @return Result operation result (success or error)
     */
    public function create(array $args)
    {
        if (empty($args) || $args === null) {
            return Result::failure('Empty arguments');
        }

        $validationResults = [];
        $validationError = false;
        $schema = static::getSchema();

        // save $args for afterCreate trigger method
        $origArgs = $args;

        $sql = $vars = null;
        $stm = null;

        static $cacheable;
        $cacheable = extension_loaded('xarray');

        $conn = $this->write;
        $driver = $conn->getQueryDriver();

        // Just a note: Exceptions should be used for exceptional conditions; things you 
        // don't expect to happen. Validating input isn't very exceptional.

        $args = $this->beforeCreate($args);
        if ($args === false) {
            return Result::failure('Create failed', [ 'args' => $args ]);
        }

        // first, filter the array, arguments for inserting data.
        $args = array_intersect_key($args, array_flip($schema->columnNames));

        // arguments that are will Bind
        $insertArgs = [];
        foreach ($schema->columns as $n => $c) {
            // if column is required (can not be empty)
            //   and default is defined.
            if (!$c->primary && (!isset($args[$n]) || !$args[$n])) {
                if ($val = $c->getDefaultValue(null, $args)) {
                    $args[$n] = $val;
                }
            }

            // if type constraint is on, check type,
            // if not, we should try to cast the type of value, 
            // if type casting is fail, we should throw an exception.

            // short alias for argument value.
            $val = isset($args[$n]) ? $args[$n] : null;

            // if column is required (can not be empty) //   and default is defined.
            // @codegenBlock validateRequire
            if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                return Result::failure("Value of $n is required.");
            }
            // @codegenBlockEnd

            // @codegenBlock typeConstraint
            if ($val !== null && !is_array($val) && !$val instanceof Raw) {
                $val = $c->typeCast($val);
            }
            // @codegenBlockEnd

            // @codegenBlock filterColumn
            if ($c->filter || $c->canonicalizer) {
                $val = $c->canonicalizeValue($val, null, $args);
            }
            // @codegenBlockEnd

            // @codegenBlock validateColumn
            if ($validationResult = static::_validateColumn($c, $val, $args, null)) {
                $validationResults[$n] = $validationResult;
                if (!$validationResult['valid']) {
                    $validationError = true;
                }
            }
            // @codegenBlockEnd

            if ($val !== null) {
                // Update filtered value back to args
                // Note that we don't deflate a scalar value, this is to prevent the overhead of data reload from database
                // We should try to keep all variables just like the row result we query from database.
                if (is_object($val) || is_array($val)) {
                    $args[$n] = $c->deflate($val, $driver);
                } else {
                    $args[$n] = $val;
                }

                if (is_scalar($val) || is_null($val)) {
                    $insertArgs[$n] = new Bind($n, $driver->cast($val));
                } elseif ($val instanceof Raw) {
                    $insertArgs[$n] = $val;
                    $cacheable = false;
                } else {
                    // deflate objects into string
                    $insertArgs[$n] = new Bind($n, $c->deflate($val, $driver));
                }
            }
        }

        // @codegenBlock handleValidationError
        if ($validationError) {
            return Result::failure('Validation failed.', [ 'validations' => $validationResults ]);
        }
        // @codegenBlockEnd

        $arguments = new ArgumentArray();

        $cacheKey = null;
        if ($cacheable) {
            $cacheKey = array_keys_join($insertArgs);
            if (isset($this->_preparedCreateStms[$cacheKey])) {
                $stm = $this->_preparedCreateStms[$cacheKey];
                foreach ($insertArgs as $name => $bind) {
                    $arguments->bind($bind);
                }
            }
        }

        if (!$stm) {
            $query = new InsertQuery();
            $query->into($this->getTable());
            $query->insert($insertArgs);
            $query->returning(static::PRIMARY_KEY);
            $sql = $query->toSql($driver, $arguments);
            $stm = $conn->prepare($sql);
            if ($cacheable) {
                $this->_preparedCreateStms[$cacheKey] = $stm;
            }
        }
        if (false === $stm->execute($arguments->toArray())) {
            return Result::failure('Record create failed.', array(
                'validations' => $validationResults,
                'args' => $args,
                'sql' => $sql,
            ));
        }

        $key = null;
        if ($driver instanceof PDOPgSQLDriver) {
            $key = intval($stm->fetchColumn());
        } else {
            $key = intval($conn->lastInsertId());
        }

        $this->afterCreate($origArgs);

        // collect debug info
        return Result::success('Record created.', [
            'key' => $key,
            'sql' => $sql,
            'args' => $args,
            'binds' => $arguments,
            'validations' => $validationResults,
            'type' => Result::TYPE_CREATE,
        ]);
    }



    /**
     * Simply create record without validation and triggers.
     *
     * @param array $args
     */
    public function rawCreate(array $args)
    {
        $conn = $this->write;

        $query = new InsertQuery();
        $query->insert($args);
        $query->into($this->getTable());
        $query->returning(static::PRIMARY_KEY);

        $driver = $conn->getQueryDriver();
        $arguments = new ArgumentArray();
        $sql = $query->toSql($driver, $arguments);

        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

        $key = null;
        if ($driver instanceof PDOPgSQLDriver) {
            $key = $stm->fetchColumn();
        } else {
            // lastInsertId is supported in SQLite and MySQL
            $key = $conn->lastInsertId();
        }
        return Result::success('Create success', [
            'key' => $key,
            'sql' => $sql,
            'type' => Result::TYPE_CREATE,
        ]);
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
                'message' => sprintf('Field %s is required.', $column->getLabel()),
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
                            'message' => sprintf('%s is not a valid value for %s', $val, $column->name),
                            'field' => $column->name,
                        );
                    }
                }
            }
        }
    }




    // ================= Locks =====================
    public function lockWrite($alias = null)
    {
        if (!$alias) {
            $alias = $this->alias;
        }
        $table = $this->getTable();
        if ($alias) {
            $this->write->query("LOCK TABLES $table AS $alias WRITE");
        } else {
            $this->write->query("LOCK TABLES $table WRITE");
        }
    }

    public function lockRead($alias = null)
    {
        if (!$alias) {
            $alias = $this->alias;
        }
        $table = $this->getTable();
        if ($alias) {
            $this->read->query("LOCK TABLES $table AS $alias READ");
        } else {
            $this->read->query("LOCK TABLES $table READ");
        }
    }

    public function unlockAll()
    {
        if ($this->readSourceId === $this->writeSourceId) {
            $this->read->query('UNLOCK TABLES');
        } else {
            $this->read->query('UNLOCK TABLES');
            $this->write->query('UNLOCK TABLES');
        }
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
}
