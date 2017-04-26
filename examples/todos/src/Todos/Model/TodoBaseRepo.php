<?php
namespace Todos\Model;

require_once __DIR__ . '/TodoSchemaProxy.php';
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Runtime\BaseModel;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use PDO;
use SQLBuilder\Universal\Query\InsertQuery;
use Maghead\Runtime\BaseRepo;

class TodoBaseRepo
    extends BaseRepo
{

    const SCHEMA_CLASS = 'Todos\\Model\\TodoSchema';

    const SCHEMA_PROXY_CLASS = 'Todos\\Model\\TodoSchemaProxy';

    const COLLECTION_CLASS = 'Todos\\Model\\TodoCollection';

    const MODEL_CLASS = 'Todos\\Model\\Todo';

    const TABLE = 'todos';

    const READ_SOURCE_ID = 'master';

    const WRITE_SOURCE_ID = 'master';

    const PRIMARY_KEY = 'id';

    const TABLE_ALIAS = 'm';

    const FIND_BY_PRIMARY_KEY_SQL = 'SELECT * FROM todos WHERE id = ? LIMIT 1';

    const DELETE_BY_PRIMARY_KEY_SQL = 'DELETE FROM todos WHERE id = ? LIMIT 1';

    public static $columnNames = array (
      0 => 'id',
      1 => 'title',
      2 => 'done',
      3 => 'description',
      4 => 'created_on',
    );

    public static $columnHash = array (
      'id' => 1,
      'title' => 1,
      'done' => 1,
      'description' => 1,
      'created_on' => 1,
    );

    public static $mixinClasses = array (
    );

    protected $table = 'todos';

    public function free()
    {
        $this->loadStm = null;
        $this->deleteStm = null;
    }

    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Todos\Model\TodoSchemaProxy;
    }

    public function findByPrimaryKey($pkId)
    {
        if (!$this->loadStm) {
           $this->loadStm = $this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);
           $this->loadStm->setFetchMode(PDO::FETCH_CLASS, 'Todos\Model\Todo', [$this]);
        }
        $this->loadStm->execute([ $pkId ]);
        $obj = $this->loadStm->fetch();
        $this->loadStm->closeCursor();
        return $obj;
    }

    public function prepareRead($sql)
    {
        return $this->read->prepare($sql);
    }

    public function prepareWrite($sql)
    {
        return $this->write->prepare($sql);
    }

    protected function unsetImmutableArgs($args)
    {
        return $args;
    }

    public function deleteByPrimaryKey($pkId)
    {
        if (!$this->deleteStm) {
           $this->deleteStm = $this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);
        }
        return $this->deleteStm->execute([$pkId]);
    }
}
