<?php
namespace Maghead\Model;
require_once __DIR__ . '/MetadataSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\BaseModel;
class MetadataBase
    extends BaseModel
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
    public static $column_names = array (
      0 => 'id',
      1 => 'name',
      2 => 'value',
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
        return $schema = new \Maghead\Model\MetadataSchemaProxy;
    }
    public static function createRepo($write, $read)
    {
        return new \Maghead\Model\MetadataBaseRepo($write, $read);
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
