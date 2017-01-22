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
use Maghead\BaseRepo;
class MetadataRepo
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
    protected $findStm;
    protected $deleteStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Maghead\Model\MetadataSchemaProxy;
    }
    public function find($pkId)
    {
        if (!$this->findStm) {
           $this->findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
           $this->findStm->setFetchMode(PDO::FETCH_CLASS, 'Maghead\Model\Metadata');
        }
        return static::_stmFetch($this->findStm, [$pkId]);
    }
    public function deleteByPrimaryKey($pkId)
    {
        if (!$this->deleteStm) {
           $this->deleteStm = $this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        }
        return $this->deleteStm->execute([$pkId]);
    }
}
