<?php

namespace Maghead\Runtime;

use Exception;
use InvalidArgumentException;
use BadMethodCallException;
use PDO;
use PDOException;
use ArrayIterator;
use LogicException;
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
use Maghead\Runtime\Bootstrap;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\RuntimeColumn;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Manager\DataSourceManager;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Runtime\Connection;
use SerializerKit\XmlSerializer;
use ActionKit;
use Symfony\Component\Yaml\Yaml;

defined('YAML_UTF8_ENCODING') || define('YAML_UTF8_ENCODING', 0);

/**
 * Base Model class,
 * every model class extends from this class.
 */
abstract class BaseModel implements Serializable
{
    use ActionCreatorTrait;
    use RepoFactoryTrait;

    public static $yamlExtension = false;

    public static $yamlEncoding = YAML_UTF8_ENCODING;

    public static $dataSourceManager;

    /**
     * @var array Mixin classes are emtpy. (MixinDeclareSchema)
     * */
    public static $mixin_classes = [];

    protected $_cache = array();

    protected $_foreignRecordCache = array();

    public $dataLabelField;

    public $dataValueField;

    protected $_schema;

    public static $_cacheInstance;

    /**
     * when reading records from repository,
     * this property will be assigned through the ctor args in the PDO::setFetchMode call.
     */
    public $repo;


    const SHARD_MAPPING_ID = null;

    const GLOBAL_TABLE = null;

    const SHARD_KEY = null;

    /**
     * When using multiple repositories, we have to know the repository where
     * the record came from for updating the record to the same repository later.
     *
     * $repo will be given from the ctor args in the PDO::setFetchMode call.
     *
     * @code
     *
     *  $book = Book::repo($write, $read)->find(1);
     *  $book->update(...);
     *
     * @endcode
     */
    public function __construct(BaseRepo $repo = null)
    {
        $this->repo = $repo;
    }

    /**
     * Get the RuntimeColumn objects from RuntimeSchema object.
     */
    public function columns()
    {
        return static::getSchema()->columns;
    }

    protected function getCachePrefix()
    {
        return static::SCHEMA_CLASS;
    }

    public function unsetPrimaryKey()
    {
        $this->setKey(null);
    }

    public function getDataLabelField()
    {
        return $this->dataLabelField ?: static::PRIMARY_KEY;
    }

    public function getDataValueField()
    {
        return $this->dataValueField ?: static::PRIMARY_KEY;
    }

    /**
     * This is for select widget,
     * returns label value from specific column.
     */
    public function dataLabel()
    {
        return $this->get($this->getDataLabelField());
    }

    /**
     * This is for select widget,
     * returns data key from specific column.
     */
    public function dataKeyValue()
    {
        return $this->get($this->getDataValueField());
    }

    /**
     * Alias method of $this->dataValue().
     */
    public function dataValue()
    {
        return $this->dataKeyValue();
    }

    /**
     * An alias for BaseRepo::findByKeys
     */
    protected static function findByKeys(array $args, $byKeys = null)
    {
        if (static::SHARD_MAPPING_ID) {
            return static::shards()->first(function (BaseRepo $repo, Shard $shard) use ($arg, $byKeys) {
                return $repo->findByKeys($args, $byKeys);
            });
        }
        return static::masterRepo()->findByKeys($args, $byKeys);
    }

    /**
     * Create or update an record by checking
     * the existence from the $byKeys array
     * that you defined.
     *
     * If the record exists, then the record should be updated.
     * If the record does not exist, then the record should be created.
     *
     * @param array $byKeys
     * @return Result
     */
    public function updateOrCreate(array $args)
    {
        if ($this->hasKey()) {
            return $this->update($args);
        }
        return static::masterRepo()->create($args);
    }


    /**
     * Create a record if the record does not exists
     * Otherwise the record should be updated with the arguments.
     *
     * @param array $args
     * @param array $byKeys it's optional if you defined primary key
     */
    public function loadOrCreate(array $args, $byKeys = null)
    {
        // FIXME: sharding
        $repo = static::masterRepo();
        $record = $repo->findByKeys($args, $byKeys);
        if ($record) {
            return $record;
        }
        $ret = $repo->create($args);
        if ($ret->error) {
            return false;
        }
        return $repo->findByPrimaryKey($ret->key);
    }


    /**
     * Return the shards used by this model.
     *
     * @return Maghead\Sharding\Shard[]
     */
    public static function shards(ShardManager $shardManager = null)
    {
        if ($shardManager) {
            return $shardManager->loadShardCollectionOf(static::SHARD_MAPPING_ID);
        }
        // Get shard nodes of this table.
        $config = Bootstrap::getConfig();
        $shardManager = new ShardManager($config, DataSourceManager::getInstance());
        return $shardManager->loadShardCollectionOf(static::SHARD_MAPPING_ID, static::REPO_CLASS);
    }

    /**
     * This static create method supports sharding.
     *
     * @param array $args
     */
    public static function create(array $args)
    {
        if (static::GLOBAL_TABLE) {
            $ret = static::masterRepo()->create($args);

            // Update primary key
            if (!isset($args[$ret->keyName])) {
                $args[$ret->keyName] = $ret->key;
            }

            // TODO: insert into shards: Check error, log and retry,
            // TODO: insert into shards: Use MAP QUERY WORKER to support async.
            // TODO: insert into shards: support global transaction
            $ret->subResults = static::shards()->map(function ($repo) use ($args) {
                return $repo->create($args);
            });
            return $ret;
        } elseif (static::SHARD_MAPPING_ID) {
            $shards = static::shards();
            $mapping = $shards->getMapping();
            $shardKeyName = $mapping->getKey();

            // If the shard is already defined,
            // then we can dispatch
            if (isset($args[$shardKeyName])) {
                // If it's primary key, we can generate an UUID for this.
                // static::GLOBAL_PRIMARY_KEY
                $shardKey = $args[$shardKeyName];
            } else {
                $column = static::getSchema()->getColumn($shardKeyName);
                if ($column->default) {
                    $shardKey = $column->getDefaultValue(null, $args);
                } else {
                    // TODO: extract the key value builder to column
                    $shardKey = $shards->generateUUID();
                }
            }

            // throw new InvalidArgumentException("shard key '{$shardKeyName}' is not defined in the argument");

            return static::shards()->locateAndExecute($shardKey, function ($repo, $shard) use ($args) {
                $ret = $repo->create($args);
                $ret->shard = $shard;
                return $ret;
            });
        }
        return static::masterRepo()->create($args);
    }


    /**
     * Create and return the created record from the master repository.
     *
     * @return BaseModel
     */
    public static function createAndLoad(array $args)
    {
        $repo = static::masterRepo();
        $ret = $repo->create($args);
        if ($ret->error) {
            return false;
        }
        return $repo->findByPrimaryKey($ret->key);
    }

    /**
     * find() is an alias method of masterRepo->find
     */
    public static function load($arg)
    {
        if (static::SHARD_MAPPING_ID) {
            return static::shards()->first(function (BaseRepo $repo, Shard $shard) use ($arg) {
                return $repo->load($arg);
            });
        }
        return static::masterRepo()->load($arg);
    }

    public static function findByPrimaryKey($arg)
    {
        if (static::SHARD_MAPPING_ID) {
            return static::shards()->first(function (BaseRepo $repo, Shard $shard) use ($arg) {
                return $repo->findByPrimaryKey($arg);
            });
        }
        return static::masterRepo()->findByPrimaryKey($arg);
    }

    public static function findWith($args)
    {
        if (static::SHARD_MAPPING_ID) {
            return static::shards()->first(function (BaseRepo $repo, Shard $shard) use ($arg) {
                return $repo->findWith($arg);
            });
        }
        return static::masterRepo()->findWith($args);
    }

    public static function loadForUpdate($args)
    {
        if (static::SHARD_MAPPING_ID) {
            return static::shards()->first(function (BaseRepo $repo, Shard $shard) use ($arg) {
                // FIXME: the update should commit the transation on the same connection.
                return $repo->loadForUpdate($arg);
            });
        }
        return static::masterRepo()->loadForUpdate($args);
    }

    public function beforeDelete()
    {
    }

    public function afterDelete()
    {
    }

    public function beforeUpdate($args)
    {
    }

    public function afterUpdate($args)
    {
    }

    /**
     * Delete current record, the record should be loaded already.
     *
     * @return Result operation result (success or error)
     */
    public function delete()
    {
        $key = $this->getKey();

        if (static::GLOBAL_TABLE) {
            $repo = static::masterRepo();

            $this->beforeDelete();
            $repo->deleteByPrimaryKey($key);
            $this->afterDelete();

            $ret = Result::success('Record deleted', [ 'type' => Result::TYPE_DELETE ]);
            $ret->subResults = static::shards()->map(function ($repo) use ($key) {
                return $repo->deleteByPrimaryKey($key);
            });
            return $ret;
        } elseif (static::SHARD_MAPPING_ID) {
            if (!$this->repo) {
                throw new LogicException("property repo is not defined. be sure to load the repo for the model.");
            }

            $this->beforeDelete($this);
            $this->repo->deleteByPrimaryKey($key);
            $this->afterDelete($this);

            return Result::success('Record deleted', [ 'type' => Result::TYPE_DELETE ]);
        }

        $repo = static::masterRepo();

        $this->beforeDelete();
        $repo->deleteByPrimaryKey($key);
        $this->afterDelete();

        return Result::success('Record deleted', [ 'type' => Result::TYPE_DELETE ]);
    }

    /**
     * Imports the current record to the target repository.
     * This method will keep all the primary keys for the created record.
     *
     * @param BaseRepo $target The target repository.
     * @return Result
     */
    public function import(BaseRepo $target)
    {
        // just a simple check
        if ($this->repo === $target) {
            throw new InvalidArgumentException("You can't move the record to the same repo.");
        }
        $args = $this->getData();
        return $target->create($args);
    }


    /**
     * Duplicates the current record to the target repository.
     *
     * This method actually creates the current record in the target
     * repository, and then deletes the current instance.
     *
     * Note: This method removes the local primary key (int + auto_increment) and global primar key (uuid keys)
     *
     * @param BaseRepo $target The target repository.
     * @return Result
     */
    public function duplicate(BaseRepo $target)
    {
        // just a simple check
        if ($this->repo === $target) {
            throw new InvalidArgumentException("You can't move the record to the same repo.");
        }
        $new = clone $this;
        $new->removeLocalPrimaryKey();
        $new->removeGlobalPrimaryKey();
        $args = $new->getData();
        return $target->create($args);
    }

    /**
     * Moves the current record to the target repository.
     *
     * This method actually creates the current record in the target
     * repository, and then deletes the current instance.
     *
     * The local primary key (int + auto_increment) will be removed in the new
     * record to prevent the duplciated key issue.
     *
     * @param BaseRepo $target The target repository.
     * @return Result
     */
    public function move(BaseRepo $target)
    {
        // just a simple check
        if ($this->repo === $target) {
            throw new InvalidArgumentException("You can't move the record to the same repo.");
        }

        $ret = $target->import($this);

        $this->beforeDelete();
        $this->repo->deleteByPrimaryKey($this->getKey());
        $this->afterDelete();

        return $ret;
    }


    /**
     * Update current record.
     *
     * @param array $args
     *
     * @return Result operation result (success or error)
     */
    public function update(array $args)
    {
        // Check if we get primary key value
        // here we allow users to specifty primary key value from arguments if the record is not loaded.
        $key = $this->getKey();
        if (!$key) {
            return Result::failure('Record is not loaded, Can not update record.', array('args' => $args));
        }

        if (static::GLOBAL_TABLE) {
            if ($a = $this->beforeUpdate($args)) {
                $args = $a;
            }

            $ret = static::masterRepo()->updateByPrimaryKey($key, $args);
            $ret->subResults = static::shards()->map(function ($repo) use ($key, $args) {
                return $repo->updateByPrimaryKey($key, $args);
            });

            $this->setData($args);

            $this->afterUpdate($args);

            return $ret;
        } elseif (static::SHARD_MAPPING_ID) {
            if (!$this->repo) {
                throw new LogicException("property repo is not defined. be sure to load the repo for the model.");
            }

            if ($a = $this->beforeUpdate($args)) {
                $args = $a;
            }

            $ret = $this->repo->updateByPrimaryKey($key, $args);
            $this->setData($args);

            $this->afterUpdate($args);

            return $ret;
        } else {
            if ($a = $this->beforeUpdate($args)) {
                $args = $a;
            }

            $ret = static::masterRepo()->updateByPrimaryKey($key, $args);
            $this->setData($args);
            $this->afterUpdate($args);

            return $ret;
        }
    }

    /**
     * Simply update record without validation and triggers.
     *
     * @param array $args
     */
    public function rawUpdate(array $args)
    {
        $key = $this->getKey();
        if (!$key) {
            return Result::failure('Record is not loaded, Can not update record.', array('args' => $args));
        }
        $ret = static::masterRepo()->rawUpdateByPrimaryKey($key, $args);
        $this->setData($args);
        return $ret;
    }

    /**
     * Simply create record without validation and triggers.
     *
     * @param array $args
     */
    public static function rawCreate(array $args)
    {
        if (static::GLOBAL_TABLE) {
            $ret = static::masterRepo()->rawCreate($args);

            // Update primary key
            if (!isset($args[$ret->keyName])) {
                $args[$ret->keyName] = $ret->key;
            }

            // TODO: insert into shards: Check error, log and retry,
            // TODO: insert into shards: Use MAP QUERY WORKER to support async.
            // TODO: insert into shards: support global transaction
            $ret->subResults = static::shards()->map(function ($repo) use ($args) {
                return $repo->rawCreate($args);
            });

            return $ret;
        } elseif (static::SHARD_MAPPING_ID) {
            $shards = static::shards();
            $mapping = $shards->getMapping();
            $shardKeyName = $mapping->getKey();

            // If the shard is already defined,
            // then we can dispatch
            if (isset($args[$shardKeyName])) {
                $shardKey = $args[$shardKeyName];
            } else {
                // Generate an UUID
                $shardKey = $shards->generateUUID();
            }

            return static::shards()->locateAndExecute($shardKey, function ($repo, $shard) use ($args) {
                $ret = $repo->rawCreate($args);
                $ret->shard = $shard;
                return $ret;
            });
        }
        return static::masterRepo()->rawCreate($args);
    }

    /**
     * Save current data (create or update)
     * if primary key is defined, do update
     * if primary key is not defined, do create.
     *
     * @return Result operation result (success or error)
     */
    public function save()
    {
        $key = $this->getKey();
        $data = $this->getData();

        // FIXME: remove immutable args
        if ($key) {
            return $this->update($data);
        }
        // FIXME: fix me for sharding
        return static::create($data);
    }

    // ============================ MIXIN METHODS ===========================

    /**
     * Find methods in mixin schema classes, methods will be called statically.
     *
     * @param string $m method name
     *
     * @return string the mixin class name.
     */
    public static function findMixinMethodClass($m)
    {
        foreach (static::$mixin_classes as $mixinClass) {
            // if we found it, just call it and return the result.
            if (method_exists($mixinClass, $m)) {
                return $mixinClass;
            }
        }

        return false;
    }


    /**
     * Invoke single mixin class method statically,.
     *
     * @param string $mixinClass mixin class name.
     * @param string $m          method name.
     * @parma array  $a method arguments.
     *
     * @return mixed execution result
     */
    protected function invokeMixinClassMethod($mixinClass, $m, array $a)
    {
        return call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
    }


    /**
     * Invoke method on all mixin classes statically. this method does not return anything.
     *
     * @param string $m method name.
     * @param array  $a method arguments.
     */
    protected function invokeAllMixinMethods($m, $a)
    {
        foreach (static::$mixin_classes as $mixinClass) {
            // If we found it, just call it and return the result.
            if (method_exists($mixinClass, $m)) {
                call_user_func_array(array($mixinClass, $m), array_merge(array($this), $a));
            }
        }
    }

    public static function __callStatic($method, $args)
    {
        $repo = static::masterRepo();
        return call_user_func_array([$repo, $method], $args);
    }

    /**
     * __call method is slower than normal method, because there are
     * one more method table to look up. you should call methods directly
     * if you need better performance.
     */
    public function __call($m, $a)
    {
        // Dispatch to schema object method first
        $schema = static::getSchema();
        if (method_exists($schema, $m)) {
            return call_user_func_array(array($schema, $m), $a);
        }

        if ($mClass = static::findMixinMethodClass($m)) {
            return $this->invokeMixinClassMethod($mClass, $m, $a);
        }

        // special case for twig template
        throw new BadMethodCallException(get_class($this).": $m method not found.");
    }



    /**
     * Render readable column value.
     *
     * @param string $name column name
     */
    public function display($name)
    {
        if ($c = static::getSchema()->getColumn($name)) {
            // get raw value
            if ($c->virtual) {
                return $this->get($name);
            }
            return $c->display($this->getValue($name));
        } elseif (isset($this->$name)) {
            return $this->$name;
        }

        // for relationship record
        $val = $this->get($name);
        if ($val && $val instanceof \Maghead\Runtime\BaseModel) {
            return $val->dataLabel();
        }
    }

    /**
     * get pdo connetion and make a query.
     *
     * @param string $sql SQL statement
     *
     * @return PDOStatement pdo statement object.
     *
     *     $stm = $this->dbQuery($sql);
     *     foreach( $stm as $row ) {
     *              $row['name'];
     *     }
     */
    public function dbQuery($dsId, $sql)
    {
        $conn = static::$dataSourceManager->getConnection($dsId);
        return $conn->query($sql);
    }

    /**
     * Get inflate value.
     *
     * @param string $name Column name
     */
    public function get($key)
    {
        // relationship id can override value column.
        if ($relation = static::getSchema()->getRelation($key)) {
            // use model query to load relational record.
            return $this->getRelationalRecords($key, $relation);
        }
        return $this->inflateColumnValue($key);
    }

    /**
     * Check if the value exist.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasValue($name)
    {
        return isset($this->$name);
    }

    /**
     * Get the raw value from record (without deflator).
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getValue($name)
    {
        return $this->$name;
    }

    /**
     * This will be overrided by child model class.
     */
    abstract public function getData();

    /**
     * Get alterable data array
     */
    abstract public function getAlterableData();


    /**
     * This will be overrided by child model class.
     */
    abstract public function setData(array $data);


    /**
     * getKey() will be generated in base classes.
     */
    abstract public function getKey();

    /**
     * hasKey() will be generated in base classes.
     */
    abstract public function hasKey();

    /**
     * setKey() will be generated in base classes.
     */
    abstract public function setKey($key);

    /**
     * getKeyName() will be generated in base classes.
     */
    abstract public function getKeyName();

    abstract public function removeLocalPrimaryKey();

    /*
    abstract public function removeGlobalPrimaryKey();

    abstract public function getGlobalPrimaryKey();
     */

    /**
     * Do we have this column ?
     *
     * @param string $name
     */
    public function __isset($name)
    {
        return property_exists($this, $name)
                || isset(static::getSchema()->columns[$name])
                || static::getSchema()->getRelation($name)
                ;
    }

    /**
     * __get magic method is used for getting:
     *
     * 1. virtual column data
     * 2. relational records
     *
     * @param string $key
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically create a model object with the relationship key for HAS-ONE relationship.
     *
     * @param string $key
     * @return \Maghead\Runtime\BaseModel
     */
    protected function fetchHasOne($key)
    {
        $cacheKey = 'relationship::'.$key;
        $relation = static::getSchema()->getRelation($key);
        $selfColumn = $relation['self_column'];
        $fSchema = $relation->newForeignSchema();
        $fColumn = $relation['foreign_column'];
        if (!$this->$selfColumn) {
            return;
        }
        $sValue = $this->$selfColumn;
        $model = $relation->newForeignModel();
        $record = $model::findWith(array($fColumn => $sValue));
        $this->setInternalCache($cacheKey, $record);
        return $record;
    }

    /**
     * Dynamically create a model object with the relationship key for BELONGS-TO relationship.
     *
     * @param string $key
     * @return \Maghead\Runtime\BaseModel
     */
    protected function fetchBelongsTo($key)
    {
        $cacheKey = 'relationship::'.$key;
        $relation = static::getSchema()->getRelation($key);
        $selfColumn = $relation['self_column'];
        $foreignSchema = $relation->newForeignSchema();
        $foreignColumn = $relation['foreign_column'];
        if (!isset($this->$selfColumn)) {
            return;
        }
        $sValue = $this->$selfColumn;
        $model = $foreignSchema->newModel();
        $record = $model::findWith([$foreignColumn => $sValue]);
        $this->setInternalCache($cacheKey, $record);
        return $record;
    }

    protected function fetchHasMany($key)
    {
        $cacheKey = 'relationship::'.$key;
        $relation = static::getSchema()->getRelation($key);

        // TODO: migrate this code to Relationship class.
        $selfColumn = $relation['self_column'];
        $fSchema = $relation->newForeignSchema();

        $fColumn = $relation['foreign_column'];

        if (!isset($this->$selfColumn)) {
            return;
        }

        $sValue = $this->$selfColumn;

        $collection = $relation->getForeignCollection();
        $collection->where()->equal($collection->getAlias().'.'.$fColumn, $sValue); // where 'm' is the default alias.

        // For if we need to create relational records
        // though collection object, we need to pre-set
        // the relational record id.
        $collection->setPresetVars([$fColumn => $sValue]);
        $this->setInternalCache($cacheKey, $collection);
        return $collection;
    }


    public function getRelationalRecords($key, $relation = null)
    {
        // check for the object cache
        $cacheKey = 'relationship::'.$key;
        if (!$relation) {
            $relation = static::getSchema()->getRelation($key);
        }

        switch ($relation['type']) {
            case Relationship::HAS_ONE:
                return $this->fetchHasOne($key);
                break;
            case Relationship::BELONGS_TO:
                return $this->fetchBelongsTo($key);
                break;
            case Relationship::HAS_MANY:
                return $this->fetchHasMany($key);
                break;
        }

        if (Relationship::MANY_TO_MANY === $relation['type']) {
            $junctionRelKey = $relation['relation_junction'];  // use relationId to get middle relation. (author_books)
            $rId2 = $relation['relation_foreign'];  // get external relationId from the middle relation. (book from author_books)

            $junctionRel = static::getSchema()->getRelation($junctionRelKey);
            if (!$junctionRel) {
                throw new InvalidArgumentException("first level relationship of many-to-many $junctionRelKey is empty");
            }

            // eg. author_books
            $sColumn = $junctionRel['foreign_column'];
            $sSchema = $junctionRel->newForeignSchema();

            $foreignRel = $sSchema->getRelation($rId2);
            if (!$foreignRel) {
                throw new InvalidArgumentException("second level relationship of many-to-many $rId2 is empty.");
            }

            $fSchema = $foreignRel->newForeignSchema();
            $fColumn = $foreignRel['foreign_column'];
            $collection = $fSchema->newCollection();

            // join middle relation ship
            //
            //    SELECT * from books b left join author_books ab on ( ab.book_id = b.id )
            //       WHERE b.author_id = :author_id
            $collection->join($sSchema->getTable())->as('b')
                            ->on()
                            ->equal('b.'.$foreignRel['self_column'], array($collection->getAlias().'.'.$fColumn));

            $value = $this->getValue($junctionRel['self_column']);
            $collection->where()->equal('b.'.$junctionRel['foreign_column'], $value);

            // for creating many-to-many subrecords, like:
            //
            //    $author->books[] = array(
            //        'author_books' => [ 'created_on' => date('c') ],
            //        'title' => 'Book Title',
            //    );
            $collection->setAfterCreate(function ($record, $args) use ($sSchema, $junctionRelKey, $junctionRel, $foreignRel, $value) {
                // arguments for creating middle-relationship record
                $a = [
                    $foreignRel['self_column'] => $record->getValue($foreignRel['foreign_column']),  // 2nd relation model id
                    $junctionRel['foreign_column'] => $value,  // self id
                ];

                if (isset($args[$junctionRelKey])) {
                    $a = array_merge($args[$junctionRelKey], $a);
                }

                // create relationship
                $middleRecord = $sSchema->newModel();
                $ret = $middleRecord::create($a);
                if ($ret->error) {
                    throw new Exception("$junctionRelKey record create failed.");
                }
                return $middleRecord::findByPrimaryKey($ret->key);
            });
            $this->setInternalCache($cacheKey, $collection);
            return $collection;
        }

        throw new Exception("The relationship type of $key is not supported.");
    }


    /**
     * Return the collection object of current model object.
     *
     * @return Maghead\Runtime\BaseCollection
     */
    public function asCollection()
    {
        $class = static::COLLECTION_CLASS;
        return $class::fromArray([$this]);
    }

    /**
     * return data stash array,.
     *
     * @return array
     */
    public function toArray(array $fields = null)
    {
        $data = $this->getData();
        if ($fields) {
            return array_intersect_key($data, array_flip($fields));
        }
        return $data;
    }

    /**
     * Deflate data and return.
     *
     * @return array
     */
    public function toInflatedArray()
    {
        $data = array();
        $schema = static::getSchema();
        $data = $this->getData();
        foreach ($data as $k => $v) {
            $col = $schema->getColumn($k);
            if ($col && $col->isa) {
                $data[ $k ] = $col->inflate($v, $this);
            } else {
                $data[ $k ] = $v;
            }
        }

        return $data;
    }

    /**
     * return json format data.
     *
     * @return string JSON string
     */
    public function toJson()
    {
        $data = $this->getData();
        return json_encode($data);
    }

    /**
     * Return xml format data.
     *
     * @return string XML string
     */
    public function toXml()
    {
        // TODO: improve element attributes
        $ser = new XmlSerializer();
        $data = $this->getData();
        return $ser->encode($data);
    }

    /**
     * Return YAML format data.
     *
     * @return string YAML string
     */
    public function toYaml()
    {
        $data = $this->getData();
        if (self::$yamlExtension) {
            return yaml_emit($data, YAML_UTF8_ENCODING);
        }
        return "---\n".Yaml::dump($data, $inline = true, $exceptionOnInvalidType = true);
    }


    /**
     * Inflate column value.
     *
     * @param string $n Column name
     *
     * @return mixed
     */
    protected function inflateColumnValue($n)
    {
        $value = property_exists($this, $n) ? $this->$n : null;
        if ($c = static::getSchema()->getColumn($n)) {
            return $c->inflate($value, $this);
        }
        return $value;
    }

    public function getDeclareSchema()
    {
        $class = static::SCHEMA_CLASS;

        return new $class();
    }

    public static function getSchema()
    {
        // This is not static property becase different model loads different schema objects.
        if ($this->_schema) {
            return $this->_schema;
        } elseif (constant('static::SCHEMA_PROXY_CLASS')) {
            if ($this->_schema = SchemaLoader::load(static::SCHEMA_PROXY_CLASS)) {
                return $this->_schema;
            }
            throw new Exception('Can not load '.static::SCHEMA_PROXY_CLASS);
        }
        throw new Exception('schema is not defined in '.get_class($this));
    }

    /***************************************
     * Cache related methods
     ***************************************/

    /**
     * flush internal cache, in php memory.
     */
    public function flushInternalCache()
    {
        $this->_cache = array();
    }

    /**
     * set internal cache, in php memory.
     *
     * @param string $key cache key
     * @param mixed  $val cache value
     *
     * @return mixed cached value
     */
    protected function setInternalCache($key, $val)
    {
        $this->_cache[ $key ] = $val;
    }

    /**
     * get internal cache from php memory.
     *
     * @param string $key cache key
     *
     * @return mixed cached value
     */
    public function getInternalCache($key)
    {
        if (isset($this->_cache[ $key ])) {
            return $this->_cache[ $key ];
        }
    }

    public function hasInternalCache($key)
    {
        return isset($this->_cache[ $key ]);
    }

    public function clearInternalCache()
    {
        $this->_cache = array();
    }


    /**
     * Used by ActionKit
     */
    public function fetchOneToManyRelationCollection($relationId)
    {
        if ($this->id && isset($this->{ $relationId })) {
            return $this->{$relationId};
        }
    }

    /**
     * Used by ActionKit
     */
    public function fetchManyToManyRelationCollection($relationId)
    {
        $schema = static::getSchema();
        $relation = $schema->getRelation($relationId);
        return $relation->newForeignForeignCollection(
            $schema->getRelation($relation['relation_junction'])
        );
    }


    // Serializable interface methods
    // ===============================
    public function serialize()
    {
        return serialize($this->getData());
    }

    public function unserialize($str)
    {
        $this->setData(unserialize($str));
    }

    /**
     * Create a record object from array.
     */
    public static function fromArray(array $array)
    {
        $record = new static;
        $record->setData($array);
        return $record;
    }
}
