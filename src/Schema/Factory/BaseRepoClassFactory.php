<?php

namespace LazyRecord\Schema\Factory;

use ClassTemplate\ClassFile;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\ConnectionManager;
use Doctrine\Common\Inflector\Inflector;
use ReflectionClass;

// used for SQL generator
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Bind;
use SQLBuilder\ParamMarker;
use SQLBuilder\ArgumentArray;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Expr\ConcatExpr;
use CodeGen\Raw;

/**
 * Base Repo class generator.
 */
class BaseRepoClassFactory
{
    public static function create(DeclareSchema $schema, $baseClass)
    {
        $cTemplate = new ClassFile($schema->getRepoClass());

        // Generate a require statement here to prevent spl autoload when
        // loading the model class.
        //
        // If the user pre-loaded the schema proxy file by the user himself,
        // then this line will cause error.
        //
        // By design, users shouldn't use the schema proxy class, it 
        // should be only used by model/collection class.
        $schemaProxyFileName = $schema->getModelName() . 'SchemaProxy.php';
        $cTemplate->prependStatement(new RequireOnceStatement(
            new ConcatExpr(new Raw('__DIR__'), DIRECTORY_SEPARATOR . $schemaProxyFileName)
        ));

        $cTemplate->useClass('LazyRecord\\Schema\\SchemaLoader');
        $cTemplate->useClass('LazyRecord\\Result');
        $cTemplate->useClass('LazyRecord\\Inflator');
        $cTemplate->useClass('SQLBuilder\\Bind');
        $cTemplate->useClass('SQLBuilder\\ArgumentArray');
        $cTemplate->useClass('PDO');
        $cTemplate->useClass('SQLBuilder\\Universal\\Query\\InsertQuery');

        $cTemplate->addConsts(array(
            'SCHEMA_CLASS'       => get_class($schema),
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'COLLECTION_CLASS'   => $schema->getCollectionClass(),
            'MODEL_CLASS'        => $schema->getModelClass(),
            'TABLE'              => $schema->getTable(),
            'READ_SOURCE_ID'     => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID'    => $schema->getWriteSourceId(),
            'PRIMARY_KEY'        => $schema->primaryKey,
            'TABLE_ALIAS'        => 'm',
        ));

        $cTemplate->addProtectedProperty('table', $schema->getTable());
        $cTemplate->addStaticVar('columnNames',  $schema->getColumnNames());
        $cTemplate->addStaticVar('columnHash',  array_fill_keys($schema->getColumnNames(), 1));
        $cTemplate->addStaticVar('mixinClasses', array_reverse($schema->getMixinSchemaClasses()));

        $cTemplate->addProtectedProperty('findStm');
        $cTemplate->addProtectedProperty('deleteStm');

        $cTemplate->addStaticMethod('public', 'getSchema', [], function() use ($schema) {
            return [
                "static \$schema;",
                "if (\$schema) {",
                "   return \$schema;",
                "}",
                "return \$schema = new \\{$schema->getSchemaProxyClass()};",
            ];
        });

        $schemaReflection = new ReflectionClass($schema);
        $schemaDocComment = $schemaReflection->getDocComment();


        $primaryKey = $schema->primaryKey;
        $readFrom = $schema->getReadSourceId();
        $writeTo  = $schema->getWriteSourceId();
        $readConnection = ConnectionManager::getInstance()->getConnection($readFrom);
        $writeConnection = ConnectionManager::getInstance()->getConnection($writeTo);
        $readQueryDriver = $readConnection->getQueryDriver();
        $writeQueryDriver = $writeConnection->getQueryDriver();

        // TODO: refacory this into factory method
        // Generate findByPrimaryKey SQL query
        $arguments = new ArgumentArray();
        $findByPrimaryKeyQuery = new SelectQuery();
        $findByPrimaryKeyQuery->from($schema->getTable());
        $primaryKeyColumn = $schema->getColumn($schema->primaryKey);
        $findByPrimaryKeyQuery->select('*')
            ->where()->equal($schema->primaryKey, new ParamMarker($schema->primaryKey));
        $findByPrimaryKeyQuery->limit(1);
        $findByPrimaryKeySql = $findByPrimaryKeyQuery->toSql($readQueryDriver, $arguments);
        $cTemplate->addConst('FIND_BY_PRIMARY_KEY_SQL', $findByPrimaryKeySql);

        $cTemplate->addMethod('public', 'find', ['$pkId'], function() use ($schema) {
            return [
                "if (!\$this->findStm) {",
                "   \$this->findStm = \$this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);",
                "   \$this->findStm->setFetchMode(PDO::FETCH_CLASS, '{$schema->getModelClass()}');",
                "}",
                "return static::_stmFetch(\$this->findStm, [\$pkId]);",
            ];
        });


        $arguments = new ArgumentArray();
        $deleteQuery = new DeleteQuery();
        $deleteQuery->delete($schema->getTable());
        $deleteQuery->where()->equal($schema->primaryKey,  new ParamMarker($schema->primaryKey));
        $deleteQuery->limit(1);
        $deleteByPrimaryKeySql = $deleteQuery->toSql($writeQueryDriver, $arguments);
        $cTemplate->addConst('DELETE_BY_PRIMARY_KEY_SQL', $deleteByPrimaryKeySql);
        $cTemplate->addMethod('public', 'deleteByPrimaryKey', ['$pkId'], function() use ($deleteByPrimaryKeySql, $schema) {
            return [
                "if (!\$this->deleteStm) {",
                "   \$this->deleteStm = \$this->write->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);",
                "}",
                "return \$this->deleteStm->execute([\$pkId]);",
            ];
        });



        foreach ($schema->getColumns() as $column) {
            if (!$column->findable) {
                continue;
            }
            $columnName = $column->name;
            $findMethodName = 'findBy'.ucfirst(Inflector::camelize($columnName));

            $findMethod = $cTemplate->addMethod('public', $findMethodName, ['$value']);
            $block = $findMethod->block;

            $arguments = new ArgumentArray();
            $findByColumnQuery = new SelectQuery();
            $findByColumnQuery->from($schema->getTable());
            $columnName = $column->name;
            $readFrom = $schema->getReadSourceId();
            $findByColumnQuery->select('*')
                ->where()->equal($columnName, new Bind($columnName));
            $findByColumnQuery->limit(1);
            $findByColumnSql = $findByColumnQuery->toSql($readQueryDriver, $arguments);

            $block[] = '$conn  = $this->getReadConnection();';

            $block[] = 'if (!isset($this->_preparedFindStms['.var_export($columnName, true).'])) {';
            $block[] = '    $this->_preparedFindStms['.var_export($columnName, true).'] = $conn->prepare('.var_export($findByColumnSql, true).');';
            $block[] = '}';
            $block[] = '$this->_preparedFindStms['.var_export($columnName, true).']->execute(['.var_export(":$columnName", true).' => $value ]);';
            $block[] = 'if (false === ($this->_data = $this->_preparedFindStms['.var_export($columnName, true).']->fetch(PDO::FETCH_ASSOC)) ) {';
            $block[] = '    return $this->reportError("Record not found", [';
            $block[] = '        "sql" => '.var_export($findByColumnSql, true).',';
            $block[] = '    ]);';
            $block[] = '}';
            $block[] = '$this->_preparedFindStms['.var_export($columnName, true).']->closeCursor();';

            $block[] = 'return $this->reportSuccess( "Data loaded", array( ';
            $block[] = '    "sql" => '.var_export($findByColumnSql, true).',';
            $block[] = '    "type" => Result::TYPE_LOAD,';
            $block[] = '));';
        }
        $cTemplate->extendClass('\\'.$baseClass);
        return $cTemplate;
    }
}
