<?php
namespace PageApp\Model;

require_once __DIR__ . '/PageSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\BaseRepo;

class PageRepo extends BaseRepo
{
    const SCHEMA_CLASS = 'PageApp\\Model\\PageSchema';
    const SCHEMA_PROXY_CLASS = 'PageApp\\Model\\PageSchemaProxy';
    const COLLECTION_CLASS = 'PageApp\\Model\\PageCollection';
    const MODEL_CLASS = 'PageApp\\Model\\Page';
    const TABLE = 'pages';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    const TABLE_ALIAS = 'm';
    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM pages WHERE id = ? LIMIT 1';
    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM pages WHERE id = ?';
    public static $columnNames = array(
      0 => 'id',
      1 => 'title',
      2 => 'brief',
      3 => 'revision_parent_id',
      4 => 'revision_root_id',
      5 => 'revision_created_at',
      6 => 'revision_updated_at',
    );
    public static $columnHash = array(
      'id' => 1,
      'title' => 1,
      'brief' => 1,
      'revision_parent_id' => 1,
      'revision_root_id' => 1,
      'revision_created_at' => 1,
      'revision_updated_at' => 1,
    );
    public static $mixinClasses = array(
      0 => 'Maghead\\Schema\\Mixin\\LocalizeMixinSchema',
      1 => 'Maghead\\Schema\\Mixin\\RevisionMixinSchema',
    );
    protected $table = 'pages';
    protected $findStm;
    protected $deleteStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
            return $schema;
        }
        return $schema = new \PageApp\Model\PageSchemaProxy;
    }
    public function find($pkId)
    {
        if (!$this->findStm) {
            $this->findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
            $this->findStm->setFetchMode(PDO::FETCH_CLASS, 'PageApp\Model\Page');
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
