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

/**
 * Base Model class generator
 *
 * Some rules for generating code:
 *
 * - Mutable values should be generated as propertes.
 * - Immutable values should be generated as constants.
 *
 */
class BaseModelClassFactory
{
    public static function create(DeclareSchema $schema, $baseClass) {
        $cTemplate = new ClassFile($schema->getBaseModelClass());

        $cTemplate->useClass('LazyRecord\\Schema\\SchemaLoader');
        $cTemplate->useClass('LazyRecord\\Result');
        $cTemplate->useClass('PDO');

        $cTemplate->addConsts(array(
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'COLLECTION_CLASS'   => $schema->getCollectionClass(),
            'MODEL_CLASS'        => $schema->getModelClass(),
            'TABLE'              => $schema->getTable(),
            'READ_SOURCE_ID'     => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID'    => $schema->getWriteSourceId(),
            'PRIMARY_KEY'        => $schema->primaryKey,
        ));

        $cTemplate->addPublicProperty('readSourceId', $schema->getReadSourceId() ?: 'default');
        $cTemplate->addPublicProperty('writeSourceId', $schema->getWriteSourceId() ?: 'default');

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


        foreach ($schema->getColumns() as $column) {
            if (!$column->findable) {
                continue;
            }
            $columnName = $column->name;
            $findMethodName = 'findBy' . ucfirst(Inflector::camelize($columnName));

            $findMethod = $cTemplate->addMethod('public', $findMethodName, ['$value']);
            $block = $findMethod->block;

            $arguments = new ArgumentArray;
            $findByColumnQuery = new SelectQuery;
            $findByColumnQuery->from($schema->getTable());
            $columnName = $column->name;
            $readFrom  = $schema->getReadSourceId();
            $findByColumnQuery->select('*')
                ->where()->equal($columnName, new Bind($columnName));
            $findByColumnQuery->limit(1);
            $findByColumnSql = $findByColumnQuery->toSql($readQueryDriver, $arguments);

            $block[] = '$conn  = $this->getReadConnection();';
            $block[] = 'if (!isset($this->_preparedFindStms[' . var_export($columnName, true ) . '])) {';
            $block[] = '    $this->_preparedFindStms[' . var_export($columnName, true ) . '] = $conn->prepare(' . var_export($findByColumnSql, true) . ');';
            $block[] = '}';
            $block[] = '$this->_preparedFindStms[' . var_export($columnName, true) . ']->execute([' .  var_export(":$columnName", true ) . ' => $value ]);';
            $block[] = 'try {';
            $block[] = '    if (false === ($this->_data = $this->_preparedFindStms[' . var_export($columnName, true ) . ']->fetch(PDO::FETCH_ASSOC)) ) {';
            $block[] = '        return $this->reportError("Record not found", [';
            $block[] = '            "sql" => ' . var_export($findByColumnSql, true) . ',';
            $block[] = '        ]);';
            $block[] = '    }';
            $block[] = '} catch (PDOException $e) {';
            $block[] = '    throw new QueryException("Record load failed", $this, $e, array(';
            $block[] = '        "sql" => ' . var_export($findByColumnSql, true)  . ',';
            $block[] = '    ));';
            $block[] = '}';
            $block[] = '$this->_preparedFindStms[' . var_export($columnName, true) . ']->closeCursor();';
            $block[] = 'return $this->reportSuccess( "Data loaded", array( ';
            $block[] = '    "sql" => ' . var_export($findByColumnSql, true) . ',';
            $block[] = '    "type" => Result::TYPE_LOAD,';
            $block[] = '));';
        }

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

