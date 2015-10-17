<?php
namespace LazyRecord\Model;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
class MetadataSchemaProxy
    extends RuntimeSchema
{
    const schema_class = 'LazyRecord\\Model\\MetadataSchema';
    const model_name = 'Metadata';
    const model_namespace = 'LazyRecord\\Model';
    const COLLECTION_CLASS = 'LazyRecord\\Model\\MetadataCollection';
    const MODEL_CLASS = 'LazyRecord\\Model\\Metadata';
    const PRIMARY_KEY = 'id';
    const TABLE = '__meta__';
    const LABEL = 'Metadata';
    public static $column_hash = array (
      'id' => 1,
      'name' => 1,
      'value' => 1,
    );
    public static $mixin_classes = array (
    );
    public $columnNames = array (
      0 => 'id',
      1 => 'name',
      2 => 'value',
    );
    public $columnNamesIncludeVirtual = array (
      0 => 'id',
      1 => 'name',
      2 => 'value',
    );
    public $label = 'Metadata';
    public $readSourceId = 'default';
    public $writeSourceId = 'default';
    public $relations;
    public function __construct()
    {
        $this->columns[ 'id' ] = new RuntimeColumn('id',array( 
      'locales' => NULL,
      'attributes' => array( 
          'autoIncrement' => true,
        ),
      'name' => 'id',
      'primary' => true,
      'unsigned' => NULL,
      'type' => 'int',
      'isa' => 'int',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'autoIncrement' => true,
    ));
        $this->columns[ 'name' ] = new RuntimeColumn('name',array( 
      'locales' => NULL,
      'attributes' => array( 
          'length' => 128,
        ),
      'name' => 'name',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'varchar',
      'isa' => 'str',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'length' => 128,
    ));
        $this->columns[ 'value' ] = new RuntimeColumn('value',array( 
      'locales' => NULL,
      'attributes' => array( 
          'length' => 256,
        ),
      'name' => 'value',
      'primary' => NULL,
      'unsigned' => NULL,
      'type' => 'varchar',
      'isa' => 'str',
      'notNull' => NULL,
      'enum' => NULL,
      'set' => NULL,
      'length' => 256,
    ));
    }
}
