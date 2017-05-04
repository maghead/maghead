<?php
namespace Todos\Model;

use Maghead\Schema\RuntimeSchema;
use Maghead\Schema\RuntimeColumn;
use Maghead\Schema\Relationship\Relationship;

class TodoSchemaProxy
    extends RuntimeSchema
{

    const schema_class = 'Todos\\Model\\TodoSchema';

    const model_name = 'Todo';

    const model_namespace = 'Todos\\Model';

    const COLLECTION_CLASS = 'Todos\\Model\\TodoCollection';

    const MODEL_CLASS = 'Todos\\Model\\Todo';

    const PRIMARY_KEY = 'id';

    const TABLE = 'todos';

    const LABEL = 'Todo';

    public static $column_hash = array (
      'id' => 1,
      'title' => 1,
      'done' => 1,
      'description' => 1,
      'created_on' => 1,
    );

    public static $mixin_classes = array (
    );

    public $columnNames = array (
      0 => 'id',
      1 => 'title',
      2 => 'done',
      3 => 'description',
      4 => 'created_on',
    );

    public $primaryKey = 'id';

    public $columnNamesIncludeVirtual = array (
      0 => 'id',
      1 => 'title',
      2 => 'done',
      3 => 'description',
      4 => 'created_on',
    );

    public $label = 'Todo';

    public $readSourceId = 'master';

    public $writeSourceId = 'master';

    public $relations;

    public function __construct()
    {
        $this->columns[ 'id' ] = new RuntimeColumn('id',array( 
      'locales' => NULL,
      'attributes' => array( 
          'length' => 16,
          'default' => function($record, $args) {
                return \Ramsey\Uuid\Uuid::uuid4()->getBytes();
            },
          'deflator' => function($val) {
                if ($val instanceof \Ramsey\Uuid\Uuid) {
                    return $val->getBytes();
                }
                return $val;
            },
          'inflator' => function($val) {
                return \Ramsey\Uuid\Uuid::fromBytes($val);
            },
        ),
      'name' => 'id',
      'primary' => true,
      'unsigned' => NULL,
      'type' => 'BINARY',
      'isa' => 'str',
      'notNull' => true,
      'enum' => NULL,
      'set' => NULL,
      'onUpdate' => NULL,
      'length' => 16,
      'default' => function($record, $args) {
                return \Ramsey\Uuid\Uuid::uuid4()->getBytes();
            },
      'deflator' => function($val) {
                if ($val instanceof \Ramsey\Uuid\Uuid) {
                    return $val->getBytes();
                }
                return $val;
            },
      'inflator' => function($val) {
                return \Ramsey\Uuid\Uuid::fromBytes($val);
            },
    ));
        $this->columns[ 'title' ] = new RuntimeColumn('title',array( 
      'locales' => NULL,
      'attributes' => array( 
          'length' => 128,
          'required' => true,
        ),
      'name' => 'title',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'varchar',
      'isa' => 'str',
      'notNull' => true,
      'enum' => NULL,
      'set' => NULL,
      'onUpdate' => NULL,
      'length' => 128,
      'required' => true,
    ));
        $this->columns[ 'done' ] = new RuntimeColumn('done',array( 
      'locales' => NULL,
      'attributes' => array( 
          'default' => false,
        ),
      'name' => 'done',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'boolean',
      'isa' => 'bool',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'onUpdate' => NULL,
      'default' => false,
    ));
        $this->columns[ 'description' ] = new RuntimeColumn('description',array( 
      'locales' => NULL,
      'attributes' => array( 
        ),
      'name' => 'description',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'text',
      'isa' => 'str',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'onUpdate' => NULL,
    ));
        $this->columns[ 'created_on' ] = new RuntimeColumn('created_on',array( 
      'locales' => NULL,
      'attributes' => array( 
          'timezone' => true,
          'default' => function() {
                    return date('c');
                },
        ),
      'name' => 'created_on',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'timestamp',
      'isa' => 'DateTime',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'onUpdate' => NULL,
      'timezone' => true,
      'default' => function() {
                    return date('c');
                },
    ));
    }
}
