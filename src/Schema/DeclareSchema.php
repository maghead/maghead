<?php

namespace Maghead\Schema;

use Exception;
use InvalidArgumentException;
use ReflectionObject;
use Maghead\ConfigLoader;
use Maghead\ClassUtils;
use Maghead\Schema\Column\AutoIncrementPrimaryKeyColumn;
use ClassTemplate\ClassTrait;
use SQLBuilder\Universal\Query\CreateIndexQuery;
use SQLBuilder\ParamMarker;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Schema\Relationship\HasMany;
use Maghead\Schema\Relationship\HasOne;
use Maghead\Schema\Relationship\BelongsTo;

class DeclareSchema extends SchemaBase implements SchemaInterface
{
    public $enableColumnAccessors = true;

    /**
     * @var string[]
     */
    public $modelTraitClasses = array();

    /**
     * @var string[]
     */
    public $collectionTraitClasses = array();

    /**
     * @var string[]
     */
    public $modelInterfaceClasses = array();

    /**
     * @var string[]
     */
    public $collectionInterfaceClasses = array();

    /**
     * @var array[string indexName] = CreateIndexQuery
     *
     * Indexes
     */
    public $indexes = array();

    public $onDelete;

    public $onUpdate;

    /**
     * Constructor of declare schema.
     *
     * The constructor calls `build` method to build the schema information.
     */
    public function __construct(array $options = array())
    {
        $this->build($options);
    }

    /**
     * Build schema build the schema by running the "schema" method.
     *
     * The post process find the primary key from the built columns
     * And insert the auto-increment primary is auto_id config is enabled.
     */
    protected function build(array $options = array())
    {
        $this->schema($options);

        // postSchema is added for mixin that needs all schema information, for example LocalizeMixin
        foreach ($this->mixinSchemas as $mixin) {
            $mixin->postSchema();
        }

        $this->primaryKey = $this->findPrimaryKey();

        // if the primary key is not define, we should append the default primary key => id
        // AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
        if (false === $this->primaryKey) {
            if ($config = ConfigLoader::getInstance()) {
                if ($config->hasAutoId() && !isset($this->columns['id'])) {
                    $this->insertAutoIdPrimaryColumn();
                }
            }
        }
    }

    public function schema()
    {
    }

    public function getWriteSourceId()
    {
        return $this->writeSourceId ?: 'default';
    }

    public function getReadSourceId()
    {
        return $this->readSourceId ?: 'default';
    }

    public function getColumns($includeVirtual = false)
    {
        if ($includeVirtual) {
            return $this->columns;
        }

        $columns = array();
        foreach ($this->columns as $name => $column) {
            // skip virtal columns
            if ($column->virtual) {
                continue;
            }
            $columns[ $name ] = $column;
        }

        return $columns;
    }

    public function getColumnLabels($includeVirtual = false)
    {
        $labels = array();
        foreach ($this->columns as $column) {
            if (!$includeVirtual && $column->virtual) {
                continue;
            }
            if ($column->label) {
                $labels[] = $column->label;
            }
        }

        return $labels;
    }

    /**
     * @param bool $includeVirtual
     *
     * @return string[]
     */
    public function getColumnNames($includeVirtual = false)
    {
        if ($includeVirtual) {
            return array_keys($this->columns);
        }

        $names = array();
        foreach ($this->columns as $name => $column) {
            if ($column->virtual) {
                continue;
            }
            $names[] = $name;
        }

        return $names;
    }

    /**
     * 'getColumn' gets the column object by the given column name.
     *
     * @param string $name
     */
    public function getColumn($name)
    {
        if (isset($this->columns[ $name ])) {
            return $this->columns[ $name ];
        }
    }

    /**
     * hasColumn method returns true if a column name is defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasColumn($name)
    {
        return isset($this->columns[ $name ]);
    }

    /**
     * Insert column object at the begining of the column list.
     *
     * @param DeclareColumn $column
     */
    public function insertColumn(DeclareColumn $column)
    {
        array_unshift($this->columnNames, $column->name);
        $this->columns = [$column->name => $column] + $this->columns;
    }

    /**
     * Insert a primary key column with auto increment.
     *
     * @param string $name       default to 'id'
     * @param string $columnType 'int', 'smallint', 'bigint' ...
     */
    protected function insertAutoIdPrimaryColumn($name = 'id', $columnType = 'integer')
    {
        $column = new AutoIncrementPrimaryKeyColumn($this, $name, $columnType);
        $this->primaryKey = $column->name;
        $this->insertColumn($column);

        return $column;
    }

    /**
     * Find primary key from columns.
     *
     * This method will be called after building schema information to save the
     * primary key name
     *
     * @return string primary key
     */
    public function findPrimaryKey()
    {
        foreach ($this->columns as $name => $column) {
            if ($column->primary) {
                return $name;
            }
        }

        return false;
    }

    /**
     * Find primary key column object from columns.
     *
     * This method will be called after building schema information to save the
     * primary key name
     *
     * @return DeclareColumn
     */
    public function findPrimaryKeyColumn()
    {
        foreach ($this->columns as $name => $column) {
            if ($column->primary) {
                return $column;
            }
        }

        return false;
    }

    public function export()
    {
        $columnArray = array();
        foreach ($this->columns as $name => $column) {
            // This idea is from:
            // http://search.cpan.org/~tsibley/Jifty-DBI-0.75/lib/Jifty/DBI/Schema.pm
            //
            // if the refer attribute is defined, we should create the belongsTo relationship
            if ($refer = $column->refer) {
                // remove _id suffix if possible
                $accessorName = preg_replace('#_id$#', '', $name);
                $schema = null;
                $schemaClass = $refer;

                // convert class name "Post" to "PostSchema"
                if (substr($refer, -strlen('Schema')) != 'Schema') {
                    if (class_exists($refer.'Schema', true)) {
                        $refer = $refer.'Schema';
                    }
                }

                if (!class_exists($refer)) {
                    throw new Exception("refer schema from '$refer' not found.");
                }

                $o = new $refer();
                // schema is defined in model
                $schemaClass = $refer;

                if (!isset($this->relations[$accessorName])) {
                    $this->belongsTo($accessorName, $schemaClass, 'id', $name);
                }
            }
            $columnArray[ $name ] = $column->export();
        }

        return array(
            'label' => $this->getLabel(),
            'table' => $this->getTable(),
            'column_data' => $columnArray,
            'column_names' => $this->columnNames,
            'primary_key' => $this->primaryKey,
            'model_class' => $this->getModelClass(),
            'collection_class' => $this->getCollectionClass(),
            'relations' => $this->relations,
            'read_data_source' => $this->readSourceId,
            'write_data_source' => $this->writeSourceId,
        );
    }

    public function dump()
    {
        return var_export($this->export(), true);
    }

    /**
     * Use trait for the model class.
     *
     * @param string $class...
     *
     * @return ClassTrait object
     */
    public function addModelTrait($traitClass)
    {
        $this->modelTraitClasses[] = $traitClass;
    }

    /**
     * Use trait for the model class.
     *
     * @param string $class...
     *
     * @return ClassTrait object
     */
    public function addCollectionTrait($traitClass)
    {
        $this->collectionTraitClasses[] = $traitClass;
    }

    /**
     * Implement interface in model class.
     *
     * @param string $class
     */
    public function addModelInterface($iface)
    {
        $this->modelInterfaceClasses[] = $iface;
    }

    /**
     * Implement interface in collection class.
     *
     * @param string $class
     */
    public function addCollectionInterface($iface)
    {
        $this->collectionInterfaceClasses[] = $iface;
    }

    /** 
     * @return string[]
     */
    public function getModelTraitClasses()
    {
        return $this->modelTraitClasses;
    }

    /** 
     * @return string[]
     */
    public function getCollectionTraitClasses()
    {
        return $this->collectionTraitClasses;
    }

    /**
     * @return string[]
     */
    public function getModelInterfaces()
    {
        return $this->modelInterfaceClasses;
    }

    /**
     * @return string[]
     */
    public function getCollectionInterfaces()
    {
        return $this->collectionInterfaceClasses;
    }

    public function getShortClassName()
    {
        $strs = explode('\\', get_class($this));

        return end($strs);
    }

    public function getClassName()
    {
        return get_class($this);
    }

    public function getLabel()
    {
        return $this->label ?: $this->_modelClassToLabel();
    }

    /**
     * Get the table name of this schema class.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?: $this->_classnameToTable();
    }

    /**
     * Get the primary key column name.
     *
     * @return string primary key column name
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get model class name of this schema.
     *
     * @return string model class name
     */
    public function getModelClass()
    {
        // If self class name is endded with 'Schema', remove it and return.
        $class = get_class($this);
        if (($p = strrpos($class, 'Schema')) !== false) {
            return substr($class, 0, $p);
        }
        // throw new Exception('Can not get model class from ' . $class );
        return $class;
    }

    /**
     * Convert current model name to a class name.
     *
     * @return string table name
     */
    protected function _classnameToTable()
    {
        return ClassUtils::convertClassToTableName($this->getModelName());
    }

    /**
     * Add column object into the column list.
     *
     * @param DeclareColumn
     *
     * @return DeclareColumn
     */
    public function addColumn(DeclareColumn $column)
    {
        if (isset($this->columns[$column->name])) {
            throw new Exception("column $name of ".get_class($this).' is already defined.');
        }
        $this->columnNames[] = $column->name;

        return $this->columns[ $column->name ] = $column;
    }

    public function removeColumn($columnName)
    {
        unset($this->columns[$columnName]);
        $this->columnNames = array_filter($this->columnNames, function ($n) use ($columnName) {
            return $n !== $columnName;
        });
    }

    protected function _modelClassToLabel()
    {
        /* Get the latest token. */
        if (preg_match('/(\w+)(?:Model)?$/', $this->getModelClass(), $reg)) {
            $label = @$reg[1];
            if (!$label) {
                throw new Exception('Table name error');
            }

            /* convert blah_blah to BlahBlah */
            return ucfirst(preg_replace('/[_]/', ' ', $label));
        }
    }

    public function __toString()
    {
        return get_class($this);
    }

    public function getMsgIds()
    {
        $ids = [];
        $ids[] = $this->getLabel();
        foreach ($this->getColumnLabels() as $label) {
            $ids[] = $label;
        }

        return $ids;
    }

    /*****************************************************************************
     * Definition Methods
     * =====================
     *
     * Methods used for defining a schema.
     ****************************************************************************/

    /**
     * Define schema label.
     *
     * @param string $label label name
     */
    public function label($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Define table name.
     *
     * @param string $table table name
     */
    public function table($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Define new column object.
     *
     * @param string $name  column name
     * @param string $class column class name
     *
     * @return DeclareColumn
     */
    public function column($name, $class = 'Maghead\\Schema\\DeclareColumn')
    {
        if (isset($this->columns[$name])) {
            throw new Exception("column $name of ".get_class($this).' is already defined.');
        }
        $this->columnNames[] = $name;

        return $this->columns[$name] = new $class($this, $name);
    }

    /**
     * SchemaGenerator generates column accessor methods from the
     * column definition automatically.
     *
     * If you don't want these accessors to be generated, you may simply call
     * 'disableColumnAccessors'
     */
    protected function disableColumnAccessors()
    {
        $this->enableColumnAccessors = false;
    }

    /**
     * Invode helper.
     *
     * @param string $helperName
     * @param array  $arguments  indexed array, passed to the init function of helper class.
     *
     * @return Helper\BaseHelper
     */
    protected function helper($helperName, array $arguments = array())
    {
        $helperClass = 'Maghead\\Schema\\Helper\\'.$helperName.'Helper';

        return new $helperClass($this, $arguments);
    }

    /**
     * Mixin.
     *
     * Availabel mixins
     *
     *     $this->mixin('Metadata' , array( options ) );
     *     $this->mixin('I18n');
     *
     * @param string $class mixin class name
     */
    public function mixin($class, array $options = array())
    {
        if (!class_exists($class, true)) {
            $class = 'Maghead\\Schema\\Mixin\\'.$class;
            if (!class_exists($class, true)) {
                throw new Exception("Mixin class $class not found.");
            }
        }

        $mixin = new $class($this, $options);
        $this->addMixinSchemaClass($class);
        $this->mixinSchemas[] = $mixin;

        /* merge columns into self */
        $this->columns = array_merge($this->columns, $mixin->columns);
        $this->relations = array_merge($this->relations, $mixin->relations);
        $this->indexes = array_merge($this->indexes, $mixin->indexes);
        $this->modelTraitClasses = array_merge($this->modelTraitClasses, $mixin->modelTraitClasses);
    }

    /**
     * set data source for both write and read.
     *
     * @param string $id data source id
     */
    public function using($id)
    {
        $this->writeSourceId = $id;
        $this->readSourceId = $id;

        return $this;
    }

    /**
     * set data source for write.
     *
     * @param string $id data source id
     */
    public function writeTo($id)
    {
        $this->writeSourceId = $id;

        return $this;
    }

    /**
     * set data source for read.
     *
     * @param string $id data source id
     */
    public function readFrom($id)
    {
        $this->readSourceId = $id;

        return $this;
    }

    /**
     * 'index' method helps you define index queries.
     *
     * @return CreateIndexQuery
     */
    protected function index($name, $columns = null, $using = null)
    {
        // return the cached index query object
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }
        $query = $this->indexes[$name] = new CreateIndexQuery($name);
        if ($columns) {
            if (!$columns || empty($columns)) {
                throw new InvalidArgumentException('index columns must not be empty.');
            }
            $query->on($this->getTable(), (array) $columns);
        }
        if ($using) {
            $query->using($using);
        }

        return $query;
    }

    /**
     * 'seeds' helps you define seed classes.
     *
     *     $this->seeds('User\\Seed','Data\\Seed');
     *
     * @return DeclareSchema
     */
    public function seeds()
    {
        $seeds = func_get_args();
        $this->seeds = array_map(function ($class) {
            return str_replace('::', '\\', $class);
        }, $seeds);

        return $this;
    }

    /**
     * Add seed class.
     *
     * @param string $seed
     */
    public function addSeed($seed)
    {
        $this->seeds[] = $seed;

        return $this;
    }

    protected function getCurrentSchemaClass()
    {
        if ($this instanceof MixinDeclareSchema) {
            return get_class($this->parentSchema);
        }

        return get_class($this);
    }

    /*****************************************************************************
     * Relationship Definition Methods
     * ===============================
     *
     * Methods used for defining relationships
     ****************************************************************************/

    /**
     * define self primary key to foreign key reference.
     *
     * comments(
     *    post_id => author.comment_id
     * )
     *
     * $post->publisher
     *
     * @param string $foreignClass  foreign schema class.
     * @param string $foreignColumn foreign reference schema column.
     * @param string $selfColumn    self column name
     */
    public function belongsTo($accessor, $foreignClass, $foreignColumn = 'id', $selfColumn = null)
    {
        $foreignClass = $this->resolveSchemaClass($foreignClass);
        // XXX: we can't create the foreign class here, because it might
        // create a recursive class loading here...
        /*
        if ($foreignClass && null === $foreignColumn) {
            $s = new $foreignClass();
            $foreignColumn = $s->primaryKey;
        }
        */
        return $this->relations[$accessor] = new BelongsTo($accessor, array(
            'type' => Relationship::BELONGS_TO,
            'self_schema' => $this->getCurrentSchemaClass(),
            'self_column' => $selfColumn,
            'foreign_schema' => $foreignClass,
            'foreign_column' => $foreignColumn,
        ));
    }

    /**
     * has-one relationship.
     *
     *   model(
     *      post_id => post
     *   )
     *
     * @param string $accessor      accessor name.
     * @param string $foreignClass  foreign schema class
     * @param string $foreignColumn foreign schema column
     * @param string $selfColumn    self schema column
     */
    public function hasOne($accessor, $foreignClass, $foreignColumn = null, $selfColumn)
    {
        // foreignColumn is default to foreignClass.primary key
        return $this->relations[ $accessor ] = new Relationship($accessor, array(
            'type' => Relationship::HAS_ONE,
            'self_schema' => $this->getCurrentSchemaClass(),
            'self_column' => $selfColumn,
            'foreign_schema' => $this->resolveSchemaClass($foreignClass),
            'foreign_column' => $foreignColumn,
        ));
    }

    /**
     * onUpdate defines a software trigger of update action.
     */
    public function onUpdate($action)
    {
        $this->onUpdate = $action;

        return $this;
    }

    /**
     * onDelete defines a software trigger of delete action.
     *
     * @param string $action Currently for 'cascade'
     */
    public function onDelete($action)
    {
        $this->onDelete = $action;

        return $this;
    }

    public function hasMany()
    {
        // forward call
        return call_user_func_array(array($this, 'many'), func_get_args());
    }

    /**
     * Add has-many relation.
     *
     * TODO: provide a relationship object to handle sush operation, that will be:
     *
     *    $this->hasMany('books','id')
     *         ->from('App_Model_Book','author_id')
     *
     *
     * @param string $accessor      accessor name.
     * @param string $foreignClass  foreign schema class
     * @param string $foreignColumn foreign schema column
     * @param string $selfColumn    self schema column
     */
    public function many($accessor, $foreignClass, $foreignColumn, $selfColumn)
    {
        return $this->relations[$accessor] = new HasMany($accessor, array(
            'type' => Relationship::HAS_MANY,
            'self_schema' => $this->getCurrentSchemaClass(),
            'self_column' => $selfColumn,
            'foreign_schema' => $this->resolveSchemaClass($foreignClass),
            'foreign_column' => $foreignColumn,
        ));
    }

    /**
     * @param string $accessor          accessor name.
     * @param string $relationId        a hasMany relationship.
     * @param string $foreignRelationId foreign relation id.
     */
    public function manyToMany($accessor, $relationId, $foreignRelationId)
    {
        if ($r = $this->getRelation($relationId)) {
            return $this->relations[ $accessor ] = new Relationship($accessor, array(
                'type' => Relationship::MANY_TO_MANY,
                'relation_junction' => $relationId,
                'relation_foreign' => $foreignRelationId,
            ));
        }
        throw new Exception("Relation $relationId is not defined.");
    }

    protected function resolveSchemaClass($class)
    {
        if (!preg_match('/Schema$/', $class)) {
            $class = $class.'Schema';
        }
        if ($class[0] == '\\' || class_exists($class)) {
            return $class;
        }
        $nsClass = $this->getNamespace().'\\'.$class;
        if (class_exists($nsClass)) {
            return $nsClass;
        }
        throw new Exception("Schema class $class or $nsClass not found.");
    }

    /*****************************************************************************
     *                            File Level Methods
     ****************************************************************************/

    /**
     * Get the related class file path by the given class name.
     *
     * @param string $class the scheam related class name
     *
     * @code
     *   $schema->getRelatedClassPath( $schema->getModelClass() );
     *   $schema->getRelatedClassPath("App\\Model\\Book"); // return {app dir}/App/Model/Book.php
     * @code
     *
     * @return string the class filepath.
     */
    public function getRelatedClassPath($class)
    {
        $_p = explode('\\', $class);
        $shortClassName = end($_p);

        return $this->getDirectory().DIRECTORY_SEPARATOR.$shortClassName.'.php';
    }

    /**
     * Get directory from current schema object.
     *
     * @return string path
     */
    public function getDirectory()
    {
        $refl = new ReflectionObject($this);

        return $dir = dirname($refl->getFilename());
    }

    public function getClassFileName()
    {
        $refl = new ReflectionObject($this);

        return $refl->getFilename();
    }

    /**
     * Return the modification time of this schema definition class.
     *
     * @return int timestamp
     */
    public function getModificationTime()
    {
        $refl = new ReflectionObject($this);

        return filemtime($refl->getFilename());
    }

    /**
     * isNewerThanFile returns true if the schema file is newer than a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isNewerThanFile($path)
    {
        if (!file_exists($path)) {
            return true;
        }

        return $this->getModificationTime() > filemtime($path);
    }

    /**
     * requireProxyFileUpdate returns true if the schema proxy file is out of date.
     *
     * @return bool
     */
    public function requireProxyFileUpdate()
    {
        $classFilePath = $this->getRelatedClassPath($this->getSchemaProxyClass());

        return $this->isNewerThanFile($classFilePath);
    }

    /**
     * @return CreateIndexQuery[]
     */
    public function getIndexQueries()
    {
        return $this->indexes;
    }

    public function newFindByPrimaryKeyQuery()
    {
        $query = $this->newSelectQuery();
        $query->where()->equal($this->primaryKey, new ParamMarker($this->primaryKey));
        $query->limit(1);
        return $query;
    }

    public function newSelectQuery()
    {
        $query = new SelectQuery();
        $query->from($this->getTable());
        $query->select('*');
        return $query;
    }


}
