<?php
namespace LazyRecord\Model;
require_once __DIR__ . '/MetadataSchemaProxy.php';
use LazyRecord\Schema\SchemaLoader;
use LazyRecord\Result;
use LazyRecord\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use LazyRecord\BaseModel;
class MetadataBase
    extends BaseModel
{
    const SCHEMA_CLASS = 'LazyRecord\\Model\\MetadataSchema';
    const SCHEMA_PROXY_CLASS = 'LazyRecord\\Model\\MetadataSchemaProxy';
    const COLLECTION_CLASS = 'LazyRecord\\Model\\MetadataCollection';
    const MODEL_CLASS = 'LazyRecord\\Model\\Metadata';
    const TABLE = '__meta__';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    const TABLE_ALIAS = 'm';
    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM __meta__ WHERE id = ? LIMIT 1';
    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM __meta__ WHERE id = ?';
    public static $column_names = array (
      0 => 'id',
      1 => 'name',
      2 => 'value',
    );
    public static $column_hash = array (
      'id' => 1,
      'name' => 1,
      'value' => 1,
    );
    public static $mixin_classes = array (
    );
    protected $table = '__meta__';
    public $readSourceId = 'default';
    public $writeSourceId = 'default';
    public $id;
    public $name;
    public $value;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \LazyRecord\Model\MetadataSchemaProxy;
    }
    public static function find($pkId)
    {
        $record = new static;
        $conn = $record->getReadConnection();
        $findStm = $conn->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
        $findStm->setFetchMode(PDO::FETCH_CLASS, 'LazyRecord\Model\Metadata');
        return static::_stmFetch($findStm, [$pkId]);
    }
    public static function deleteByPrimaryKey($pkId)
    {
        $record = new static;
        $conn = $record->getWriteConnection();
        $stm = $conn->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        return $stm->execute([$pkId]);
    }
    public function getKeyName()
    {
        return 'id';
    }
    public function getKey()
    {
        return $this->id;
    }
    public function hasKey()
    {
        return isset($this->id);
    }
    public function setKey($key)
    {
        return $this->id = $key;
    }
    public function getData()
    {
        return ["id" => $this->id, "name" => $this->name, "value" => $this->value];
    }
    public function setData(array $data)
    {
        if (array_key_exists("id", $data)) { $this->id = $data["id"]; }
        if (array_key_exists("name", $data)) { $this->name = $data["name"]; }
        if (array_key_exists("value", $data)) { $this->value = $data["value"]; }
    }
    public function clear()
    {
        $this->id = NULL;
        $this->name = NULL;
        $this->value = NULL;
    }
}
