<?php
namespace AuthorBooks\Model;

require_once __DIR__ . '/AuthorBookSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\Runtime\Repo;

class AuthorBookRepo extends Repo
{
    const SCHEMA_CLASS = 'AuthorBooks\\Model\\AuthorBookSchema';
    const SCHEMA_PROXY_CLASS = 'AuthorBooks\\Model\\AuthorBookSchemaProxy';
    const COLLECTION_CLASS = 'AuthorBooks\\Model\\AuthorBookCollection';
    const MODEL_CLASS = 'AuthorBooks\\Model\\AuthorBook';
    const TABLE = 'author_books';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    const TABLE_ALIAS = 'm';
    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM author_books WHERE id = ? LIMIT 1';
    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM author_books WHERE id = ?';
    public static $columnNames = array(
      0 => 'id',
      1 => 'author_id',
      2 => 'book_id',
      3 => 'created_on',
    );
    public static $columnHash = array(
      'id' => 1,
      'author_id' => 1,
      'book_id' => 1,
      'created_on' => 1,
    );
    public static $mixinClasses = array(
    );
    protected $table = 'author_books';
    protected $findStm;
    protected $deleteStm;
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
            return $schema;
        }
        return $schema = new \AuthorBooks\Model\AuthorBookSchemaProxy;
    }
    public function find($pkId)
    {
        if (!$this->findStm) {
            $this->findStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
            $this->findStm->setFetchMode(PDO::FETCH_CLASS, 'AuthorBooks\Model\AuthorBook');
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
