<?php
namespace Maghead\Model;
require_once __DIR__ . '/MetadataSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\BaseModel;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\BaseRepo;
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
    protected $loadByNameStm;
    protected $loadByValueStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Maghead\Model\MetadataSchemaProxy;
    }
    public function loadByPrimaryKey($pkId)
    {
        if (!$this->loadStm) {
           $this->loadStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
           $this->loadStm->setFetchMode(PDO::FETCH_CLASS, 'Maghead\Model\Metadata');
        }
        return static::_stmFetchOne($this->loadStm, [$pkId]);
    }
    public function loadByName($value)
    {
        if (!$this->loadByNameStm) {
            $this->loadByNameStm = $this->read->prepare(self::LOAD_BY_NAME_SQL);
            $this->loadByNameStm->setFetchMode(PDO::FETCH_CLASS, '\Maghead\Model\Metadata');
        }
        return static::_stmFetchOne($this->loadByNameStm, [':name' => $value ]);
    }
    public function loadByValue($value)
    {
        if (!$this->loadByValueStm) {
            $this->loadByValueStm = $this->read->prepare(self::LOAD_BY_VALUE_SQL);
            $this->loadByValueStm->setFetchMode(PDO::FETCH_CLASS, '\Maghead\Model\Metadata');
        }
        return static::_stmFetchOne($this->loadByValueStm, [':value' => $value ]);
    }
    public function deleteByPrimaryKey($pkId)
    {
        if (!$this->deleteStm) {
           $this->deleteStm = $this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        }
        return $this->deleteStm->execute([$pkId]);
    }
}
