<?php
namespace AuthorBooks\Model;

require_once __DIR__ . '/AddressSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use Magsql\Bind;
use Magsql\ArgumentArray;
use PDO;
use Magsql\Universal\Query\InsertQuery;
use Maghead\Runtime\Repo;

class AddressRepo extends Repo
{
    const SCHEMA_CLASS = 'AuthorBooks\\Model\\AddressSchema';
    const SCHEMA_PROXY_CLASS = 'AuthorBooks\\Model\\AddressSchemaProxy';
    const COLLECTION_CLASS = 'AuthorBooks\\Model\\AddressCollection';
    const RECORD_CLASS = 'AuthorBooks\\Model\\Address';
    const TABLE = 'addresses';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    const TABLE_ALIAS = 'm';
    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM addresses WHERE id = ? LIMIT 1';
    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM addresses WHERE id = ?';
    public static $columnNames = array(
      0 => 'id',
      1 => 'author_id',
      2 => 'address',
      3 => 'unused',
    );
    public static $columnHash = array(
      'id' => 1,
      'author_id' => 1,
      'address' => 1,
      'unused' => 1,
    );
    public static $mixinClasses = array(
    );
    protected $table = 'addresses';
    protected $findStm;
    protected $deleteStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
            return $schema;
        }
        return $schema = new \AuthorBooks\Model\AddressSchemaProxy;
    }
    public function find($pkId)
    {
        if (!$this->findStm) {
            $this->findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
            $this->findStm->setFetchMode(PDO::FETCH_CLASS, 'AuthorBooks\Model\Address');
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
