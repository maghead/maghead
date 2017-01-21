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
}
