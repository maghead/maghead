<?php

namespace Maghead\Runtime;

use PDOStatement;
use PDO;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use BadMethodCallException;
use ArrayIterator;
use Serializable;
use ArrayAccess;

use Maghead\Runtime\Query\SelectQuery;
use Maghead\Runtime\Query\DeleteQuery;
use Maghead\Runtime\Query\UpdateQuery;

use SQLBuilder\ToSqlInterface;
use SQLBuilder\Universal\Query\InsertQuery;

use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Raw;

use Maghead\Connection;
use Maghead\Runtime\Result\OperationError;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\RuntimeColumn;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Exception\MissingPrimaryKeyException;
use Maghead\Exception\QueryException;
use SerializerKit\XmlSerializer;
use ActionKit;
use Symfony\Component\Yaml\Yaml;
use Countable;

use Maghead\Sharding\Traits\RepoShardTrait;
use Maghead\Sharding\Shard;

abstract class BaseRepo implements Countable
{
    // Move this to Repo class Generator
    use RepoShardTrait;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $alias;


    /**
     * @var Maghead\Connection
     */
    protected $write;

    /**
     * @var Maghead\Connection
     */
    protected $read;


    /**
     * @var Maghead\Sharding\Shard
     */
    protected $shard;

    protected $_preparedCreateStms = [];

    /**
     * @var PDOStatement
     */
    protected $loadStm;

    /**
     * @var PDOStatement
     */
    protected $deleteStm;

    const SHARD_MAPPING_ID = null;

    const GLOBAL_TABLE = null;

    const SHARD_KEY = null;

    /**
     * Unset immutable args
     */
    abstract protected function unsetImmutableArgs($args);

    public function __construct(Connection $write, Connection $read = null, Shard $shard = null)
    {
        $this->write = $write;
        $this->read = $read ? $read : $write;
        $this->shard = $shard;
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

    /**
     * Returns the current alias, if it's not set, a default TABLE_ALIAS will
     * be returned.
     */
    public function getAlias()
    {
        return $this->alias ?: static::TABLE_ALIAS;
    }

    /**
     * Replace the default alias.
     *
     * @param string $aliaa
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }


    public function getShard()
    {
        return $this->shard;
    }

    /**
     * Load method loads one record from the repository with compound conditions.
     *
     * @param array $args
     */
    public function findWith(array $args)
    {
        $schema = $this->getSchema();
        $query = new SelectQuery($this);
        $query->select('*');
        $query->from($this->getTable(), $this->getAlias());

        $conn = $this->read;
        $driver = $conn->getQueryDriver();
        $query->where($args);

        $arguments = new ArgumentArray();
        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, static::MODEL_CLASS, [$this]);
        $stm->execute($arguments->toArray());

        return $stm->fetch(PDO::FETCH_CLASS);
    }

    public function findByKeys(array $args, $byKeys = null)
    {
        $pk = static::PRIMARY_KEY;
        $record = null;
        if ($pk && isset($args[$pk])) {
            return $this->findByPrimaryKey($args[$pk]);
        } elseif ($byKeys) {
            $conds = [];
            foreach ((array) $byKeys as $k) {
                if (array_key_exists($k, $args)) {
                    $conds[$k] = $args[$k];
                }
            }
            return $this->findWith($conds);
        }
        throw new MissingPrimaryKeyException('primary key is not defined.');
    }

    /**
     * Inserts the record into the repository but local keys will be removed
     * before the insertion.
     *
     * @param BaseModel $record
     */
    public function import(BaseModel $record)
    {
        $new = clone $record;
        $new->removeLocalPrimaryKey();
        $args = $new->getData();
        return $this->create($args);
    }


    public function load($arg)
    {
        if (is_array($arg)) {
            return $this->findWith($arg);
        }
        return $this->findByPrimaryKey($arg);
    }

    public function loadForUpdate(array $args)
    {
        $conn = $this->read;
        $schema = $this->getSchema();
        $driver = $conn->getQueryDriver();

        if (!$driver instanceof PDOMySQLDriver) {
            throw new Exception("The current driver doesn't support SELECT ... FOR UPDATE");
        }

        $query = new SelectQuery($this);
        $query->select('*');
        $query->from($this->getTable(), $this->getAlias());
        $query->forUpdate();
        $query->where($args);

        $arguments = new ArgumentArray();

        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, static::MODEL_CLASS, [$this]);
        $stm->execute($arguments->toArray());

        return $stm->fetch(PDO::FETCH_CLASS);
    }


    public function rawUpdateByPrimaryKey($kVal, array $args)
    {
        $conn   = $this->write;
        $driver = $conn->getQueryDriver();

        $arguments = new ArgumentArray();
        $query = new UpdateQuery($this);
        $query->set($args);
        $query->update($this->getTable());
        $query->where()->equal(static::PRIMARY_KEY, $kVal);
        $sql = $query->toSql($driver, $arguments);
        $stm = $conn->prepare($sql);
        $stm->execute($arguments->toArray());

        return Result::success('Updated', [
            'key' => $kVal,
            'keyName' => static::PRIMARY_KEY,
            'sql' => $sql,
            'type' => Result::TYPE_UPDATE,
        ]);
    }

    public function updateByPrimaryKey($kVal, array $args)
    {
        $schema = static::getSchema();

        $args = $this->unsetImmutableArgs($args);

        // backup the arguments
        $origArgs = $args;
        $updateArgs = [];

        $conn = $this->write;
        $driver = $conn->getQueryDriver();

        $query = new UpdateQuery($this);

        $validationError = false;
        $validationResults = [];

        if ($args === false) {
            return Result::failure('Update failed', [ 'args' => $args ]);
        }

        $record = $this->findByPrimaryKey($kVal);

        $arguments = new ArgumentArray();

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

            // FIXME check immutable 
            // if column is required (can not be empty) //   and default is defined.
            if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                return Result::failure("Value of $n is required.");
            }

            // TODO: Do not render immutable field in ActionKit
            // FIXME: calling ::save() might update the immutable columns
            if ($c->immutable) {
                continue;
                // FIXME: raise the error
                // return Result::failure( "You can not update $n column, which is immutable.", array('args' => $args));
                // continue;
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

        return Result::success('Updated successfully', array(
            'key' => $kVal,
            'keyName' => static::PRIMARY_KEY,
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
        if (empty($args)) {
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
            if (!isset($args[$n]) || !$args[$n]) {
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
            return Result::failure('Record create failed.', [
                'validations' => $validationResults,
                'args' => $args,
                'sql' => $sql,
            ]);
        }

        // For integer primary key, we should convert it to intval
        $key = null;
        if ($primaryKey = $schema->getColumn(static::PRIMARY_KEY)) {
            if (isset($args[static::PRIMARY_KEY])) {
                $key = $primaryKey->typeCast($args[static::PRIMARY_KEY]);
            } else {
                if ($driver instanceof PDOPgSQLDriver) {
                    $key = $primaryKey->typeCast($stm->fetchColumn());
                } else {
                    $key = $primaryKey->typeCast($conn->lastInsertId());
                }
            }
        }

        $this->afterCreate($origArgs);

        // collect debug info
        return Result::success('Record created.', [
            'key' => $key,
            'keyName' => static::PRIMARY_KEY,
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
            'keyName' => static::PRIMARY_KEY,
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
    public static function _validateColumn(RuntimeColumn $column, $val, array $args, $record)
    {
        // check for requried columns
        if ($column->required && ($val === '' || $val === null)) {
            return array(
                'valid' => false,
                'message' => sprintf('Field %s is required.', $column->getLabel()),
                'field' => $column->name,
            );
        }

        // TODO: migrate this section to runtime column
        // required variables: $record, $args, $val
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


    /**
     * PDO::exec — Execute an SQL statement and return the number of affected rows
     *
     * @return int
     */
    public function exec($sql)
    {
        return $this->write->exec($sql);
    }

    /**
     * Execute plain SQL query in the write connection.
     *
     * Return the result of PDOStatement::execute method.
     *
     * @return bool
     */
    public function write($sql, $args)
    {
        $stm = $this->write->prepare($sql, $args);
        return $stm->execute($args);
    }

    /**
     * Execute a query in the repo.
     *
     * This method executes PDOStatement::execute and return the result directly.
     *
     * @return [bool, string sql, array args]
     */
    public function execute(ToSqlInterface $query)
    {
        $arguments = new ArgumentArray;
        $driver = $this->write->getQueryDriver();
        $sql = $query->toSql($driver, $arguments);
        $stm = $this->write->prepare($sql);
        return $stm->execute($arguments->toArray());
    }

    public function executeAndFetchAll(ToSqlInterface $query, $fetchMode = PDO::FETCH_OBJ)
    {
        $arguments = new ArgumentArray;
        $driver = $this->write->getQueryDriver();
        $sql = $query->toSql($driver, $arguments);
        $stm = $this->write->prepare($sql);
        if ($stm->execute($arguments->toArray())) {
            return $stm->fetchAll($fetchMode);
        }
        return false;
    }



    /**
     * Executes a select query in the read connection and fetch the column from the result.
     *
     * @param SelectQuery $query
     * @param ArgumentArray $args
     *
     * @return any[] Return the mixed values in an array.
     */
    public function fetchColumn(SelectQuery $query, $column = 0)
    {
        $driver = $this->read->getQueryDriver();
        $arguments = new ArgumentArray;
        $sql = $query->toSql($driver, $arguments);
        $stm = $this->read->prepare($sql);
        $stm->execute($arguments->toArray());
        return $stm->fetchAll(PDO::FETCH_COLUMN, $column);
    }

    /**
     * Executes a select query in the read connection
     * and return the collection with the current repo object and the current stm object.
     *
     * @param SelectQuery $query
     * @param ArgumentArray $args
     *
     * @return Maghead\Runtime\BaseCollection
     */
    public function fetchCollection(SelectQuery $query)
    {
        $driver = $this->read->getQueryDriver();
        $arguments = new ArgumentArray;
        $sql = $query->toSql($driver, $arguments);

        $stm = $this->read->prepare($sql);
        $stm->setFetchMode(PDO::FETCH_CLASS, static::MODEL_CLASS, [$this]);
        $stm->execute($arguments->toArray());

        // Create collection object with the current repo and the current PDOStatement
        $cls = static::COLLECTION_CLASS;
        return new $cls($this, $stm);
    }


    /**
     * @param SelectQuery $query
     *
     * @return array
     */
    public function fetchAll(SelectQuery $query, $fetchMode = PDO::FETCH_OBJ)
    {
        $driver = $this->read->getQueryDriver();
        $arguments = new ArgumentArray;
        $sql = $query->toSql($driver, $arguments);

        $stm = $this->read->prepare($sql);
        $stm->execute($arguments->toArray());
        return $stm->fetchAll($fetchMode);
    }



    // Countable interface
    // ==============================================
    public function count()
    {
        $pk = static::PRIMARY_KEY;
        return $this->select("COUNT(m.{$pk})", "m")->fetchColumn(0);
    }

    // ================= QUERY METHODS =============



    /**
     * @return Maghead\Runtime\Query\SelectQuery
     */
    public function select($sel = '*', $alias = 'm')
    {
        $query = new SelectQuery($this);
        $query->from(static::TABLE, $alias); // main table alias
        $query->setSelect($sel); // default selection
        return $query;
    }

    /**
     * @return Maghead\Runtime\Query\DeleteQuery
     */
    public function delete()
    {
        $query = new DeleteQuery($this);
        $query->from(static::TABLE); // main table alias
        return $query;
    }

    /**
     * @return Maghead\Runtime\Query\UpdateQuery
     */
    public function update($data = null)
    {
        $query = new UpdateQuery($this);
        $query->update(static::TABLE);
        $query->set($data);
        return $query;
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
}
