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
use LazyRecord\BaseRepo;
class MetadataRepo
    extends BaseRepo
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
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \LazyRecord\Model\MetadataSchemaProxy;
    }
    public function find($pkId)
    {
        $findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
        $findStm->setFetchMode(PDO::FETCH_CLASS, 'LazyRecord\Model\Metadata');
        return static::_stmFetch($findStm, [$pkId]);
    }
    public function deleteByPrimaryKey($pkId)
    {
        $stm = $this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        return $stm->execute([$pkId]);
    }
}
