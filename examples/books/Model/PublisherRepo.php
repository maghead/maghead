<?php
namespace AuthorBooks\Model;

require_once __DIR__ . '/PublisherSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\BaseRepo;

class PublisherRepo extends BaseRepo
{
    const SCHEMA_CLASS = 'AuthorBooks\\Model\\PublisherSchema';
    const SCHEMA_PROXY_CLASS = 'AuthorBooks\\Model\\PublisherSchemaProxy';
    const COLLECTION_CLASS = 'AuthorBooks\\Model\\PublisherCollection';
    const MODEL_CLASS = 'AuthorBooks\\Model\\Publisher';
    const TABLE = 'publishers';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    const TABLE_ALIAS = 'm';
    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM publishers WHERE id = ? LIMIT 1';
    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM publishers WHERE id = ?';
    public static $columnNames = array(
      0 => 'id',
      1 => 'name',
    );
    public static $columnHash = array(
      'id' => 1,
      'name' => 1,
    );
    public static $mixinClasses = array(
    );
    protected $table = 'publishers';
    protected $findStm;
    protected $deleteStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
            return $schema;
        }
        return $schema = new \AuthorBooks\Model\PublisherSchemaProxy;
    }
    public function find($pkId)
    {
        if (!$this->findStm) {
            $this->findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
            $this->findStm->setFetchMode(PDO::FETCH_CLASS, 'AuthorBooks\Model\Publisher');
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
