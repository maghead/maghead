<?php
namespace Todos\Model;

require_once __DIR__ . '/TodoSchemaProxy.php';
use Maghead\Runtime\BaseModel;
use Maghead\Schema\SchemaLoader;
use Maghead\Result;
use Maghead\Inflator;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use DateTime;

class TodoBase
    extends BaseModel
{

    const SCHEMA_CLASS = 'Todos\\Model\\TodoSchema';

    const SCHEMA_PROXY_CLASS = 'Todos\\Model\\TodoSchemaProxy';

    const COLLECTION_CLASS = 'Todos\\Model\\TodoCollection';

    const MODEL_CLASS = 'Todos\\Model\\Todo';

    const REPO_CLASS = 'Todos\\Model\\TodoBaseRepo';

    const TABLE = 'todos';

    const READ_SOURCE_ID = 'master';

    const WRITE_SOURCE_ID = 'master';

    const PRIMARY_KEY = 'id';

    const TABLE_ALIAS = 'm';

    const GLOBAL_PRIMARY_KEY = 'id';

    const LOCAL_PRIMARY_KEY = NULL;

    public static $column_names = array (
      0 => 'id',
      1 => 'title',
      2 => 'done',
      3 => 'description',
      4 => 'created_on',
    );

    public static $mixin_classes = array (
    );

    protected $table = 'todos';

    public $id;

    public $title;

    public $done;

    public $description;

    public $created_on;

    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Todos\Model\TodoSchemaProxy;
    }

    public static function createRepo($write, $read)
    {
        return new \Todos\Model\TodoBaseRepo($write, $read);
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

    public function removeLocalPrimaryKey()
    {
    }

    public function removeGlobalPrimaryKey()
    {
        $this->id = null;
    }

    public function getId()
    {
        if ($c = $this->getSchema()->getColumn("id")) {
             return $c->inflate($this->id, $this);
        }
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function isDone()
    {
        $value = $this->done;
        if ($value === '' || $value === null) {
           return null;
        }
        return boolval($value);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getCreatedOn()
    {
        return Inflator::inflate($this->created_on, 'DateTime');
    }

    public function getData()
    {
        return ["id" => $this->id, "title" => $this->title, "done" => $this->done, "description" => $this->description, "created_on" => $this->created_on];
    }

    public function setData(array $data)
    {
        if (array_key_exists("id", $data)) { $this->id = $data["id"]; }
        if (array_key_exists("title", $data)) { $this->title = $data["title"]; }
        if (array_key_exists("done", $data)) { $this->done = $data["done"]; }
        if (array_key_exists("description", $data)) { $this->description = $data["description"]; }
        if (array_key_exists("created_on", $data)) { $this->created_on = $data["created_on"]; }
    }

    public function clear()
    {
        $this->id = NULL;
        $this->title = NULL;
        $this->done = NULL;
        $this->description = NULL;
        $this->created_on = NULL;
    }
}
