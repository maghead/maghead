<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\ConnectionManager;
use Doctrine\Common\Inflector\Inflector;


// used for SQL generator
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\UpdateQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Universal\Query\InsertQuery;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\Bind;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Raw;

class BaseModelClassFactory
{
    public static function create(DeclareSchema $schema, $baseClass) {
        $cTemplate = new ClassFile($schema->getBaseModelClass());

        $cTemplate->useClass('LazyRecord\\Schema\\SchemaLoader');

        $cTemplate->addConsts(array(
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'COLLECTION_CLASS'   => $schema->getCollectionClass(),
            'MODEL_CLASS'        => $schema->getModelClass(),
            'TABLE'              => $schema->getTable(),
            'READ_SOURCE_ID'     => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID'    => $schema->getWriteSourceId(),
            'PRIMARY_KEY'        => $schema->primaryKey,
        ));

        $cTemplate->addMethod('public', 'getSchema', [], [
            'if ($this->_schema) {',
            '   return $this->_schema;',
            '}',
            'return $this->_schema = SchemaLoader::load(' . var_export($schema->getSchemaProxyClass(),true) .  ');',
        ]);

        $cTemplate->addStaticVar('column_names',  $schema->getColumnNames());
        $cTemplate->addStaticVar('column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar('mixin_classes', array_reverse($schema->getMixinSchemaClasses()) );

        if ($traitClasses = $schema->getModelTraitClasses()) {
            foreach($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }


        // TODO: refacory this into factory method
        // Generate findByPrimaryKey SQL query
        $arguments = new ArgumentArray;
        $findByPrimaryKeyQuery = new SelectQuery;
        $findByPrimaryKeyQuery->from($schema->getTable());
        $primaryKey = $schema->primaryKey;
        $readFrom  = $schema->getReadSourceId();
        $readConnection = ConnectionManager::getInstance()->getConnection($readFrom);
        $readQueryDriver = $readConnection->createQueryDriver();
        $primaryKeyColumn = $schema->getColumn($primaryKey);
        $findByPrimaryKeyQuery->select('*')
            ->where()->equal($primaryKey, new Bind($primaryKey));
        $findByPrimaryKeyQuery->limit(1);
        $findByPrimaryKeySql = $findByPrimaryKeyQuery->toSql($readQueryDriver, $arguments);
        $cTemplate->addConst('FIND_BY_PRIMARY_KEY_SQL', $findByPrimaryKeySql);


        /*
         * TODO: filter findable columns
        foreach ($schema->getColumns() as $column) {

        }
         */

        $cTemplate->extendClass( '\\' . $baseClass );

        // interfaces
        if ($ifs = $schema->getModelInterfaces()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }

        // Create column accessor
        if ($schema->enableColumnAccessors) {
            foreach ($schema->getColumnNames() as $columnName) {
                $accessorMethodName = 'get' . ucfirst(Inflector::camelize($columnName));
                $cTemplate->addMethod('public', $accessorMethodName, [], [
                    '    return $this->get(' . var_export($columnName, true) . ');',
                ]);
            }
        }

        return $cTemplate;
    }
}

