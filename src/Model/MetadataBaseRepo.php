<?php
namespace Maghead\Model;

require_once __DIR__ . '/MetadataSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Runtime\BaseModel;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\Runtime\BaseRepo;

class MetadataBaseRepo
    extends BaseRepo
{

    const SCHEMA_CLASS = 'Maghead\\Model\\MetadataSchema';

    const SCHEMA_PROXY_CLASS = 'Maghead\\Model\\MetadataSchemaProxy';

    const COLLECTION_CLASS = 'Maghead\\Model\\MetadataCollection';

    const MODEL_CLASS = 'Maghead\\Model\\Metadata';

    const TABLE = '__meta__';

    const READ_SOURCE_ID = 'default';

    const WRITE_SOURCE_ID = 'default';

    const PRIMARY_KEY = 'id';

    const TABLE_ALIAS = 'm';

    const SHARD_MAPPING_ID = NULL;

    const GLOBAL_TABLE = false;

    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM __meta__ WHERE id = ? LIMIT 1';

    const LOAD_BY_NAME_SQL = 'SELECT * FROM __meta__ WHERE name = :name LIMIT 1';

    const LOAD_BY_VALUE_SQL = 'SELECT * FROM __meta__ WHERE value = :value LIMIT 1';

    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM __meta__ WHERE id = ?';

    public static $columnNames = array (
      0 => 'id',
      1 => 'name',
      2 => 'value',
    );

    public static $columnHash = array (
      'id' => 1,
      'name' => 1,
      'value' => 1,
    );

    public static $mixinClasses = array (
    );

    protected $table = '__meta__';

    protected $loadStm;

    protected $deleteStm;

    protected $findByNameStm;

    protected $findByValueStm;

    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Maghead\Model\MetadataSchemaProxy;
    }

    public function findByPrimaryKey($pkId)
    {
        if (!$this->loadStm) {
           $this->loadStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
           $this->loadStm->setFetchMode(PDO::FETCH_CLASS, 'Maghead\Model\Metadata', [$this]);
        }
        return static::_stmFetchOne($this->loadStm, [$pkId]);
    }

    public function prepareRead($sql)
    {
        return $this->read->prepare($sql);
    }

    public function prepareWrite($sql)
    {
        return $this->write->prepare($sql);
    }

    public function findByName($value)
    {
        if (!$this->findByNameStm) {
            $this->findByNameStm = $this->read->prepare(self::LOAD_BY_NAME_SQL);
            $this->findByNameStm->setFetchMode(PDO::FETCH_CLASS, '\Maghead\Model\Metadata', [$this]);
        }
        $this->findByNameStm->execute([':name' => $value ]);
        $obj = $this->findByNameStm->fetch();
        $this->findByNameStm->closeCursor();
        return $obj;
    }

    public function findByValue($value)
    {
        if (!$this->findByValueStm) {
            $this->findByValueStm = $this->read->prepare(self::LOAD_BY_VALUE_SQL);
            $this->findByValueStm->setFetchMode(PDO::FETCH_CLASS, '\Maghead\Model\Metadata', [$this]);
        }
        $this->findByValueStm->execute([':value' => $value ]);
        $obj = $this->findByValueStm->fetch();
        $this->findByValueStm->closeCursor();
        return $obj;
    }

    public function deleteByPrimaryKey($pkId)
    {
        if (!$this->deleteStm) {
           $this->deleteStm = $this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        }
        return $this->deleteStm->execute([$pkId]);
    }
}
