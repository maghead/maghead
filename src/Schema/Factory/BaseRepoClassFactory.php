<?php

namespace Maghead\Schema\Factory;

use ClassTemplate\ClassFile;
use Maghead\ConnectionManager;
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\PDOStatementCodeGen;
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

use Maghead\Schema\CodeGenSettingsParser;
use Maghead\Schema\AnnotatedBlock;
use Maghead\Schema\MethodBlockParser;
use Maghead\Schema\Relationship\Relationship;


/**
 * Base Repo class generator.
 */
class BaseRepoClassFactory
{

    public static function create(DeclareSchema $schema, $baseClass)
    {
        $readFrom = $schema->getReadSourceId();
        $writeTo  = $schema->getWriteSourceId();

        $readConnection = ConnectionManager::getInstance()->getConnection($readFrom);
        $readQueryDriver = $readConnection->getQueryDriver();

        $writeConnection = ConnectionManager::getInstance()->getConnection($writeTo);
        $writeQueryDriver = $writeConnection->getQueryDriver();

        $cTemplate = new ClassFile($schema->getBaseRepoClass());

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

        $cTemplate->useClass('Maghead\\Schema\\SchemaLoader');
        $cTemplate->useClass('Maghead\\Result');
        $cTemplate->useClass('Maghead\\BaseModel');
        $cTemplate->useClass('Maghead\\Inflator');
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

        $cTemplate->addProtectedProperty('loadStm');
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



        // parse codegen settings from schema doc comment string
        $codegenSettings = CodeGenSettingsParser::parse($schemaDocComment);
        if (!empty($codegenSettings)) {
            $reflectionRepo = new ReflectionClass('Maghead\\BaseRepo');
            $createMethod = $reflectionRepo->getMethod('create');
            $elements = MethodBlockParser::parseElements($createMethod, 'codegenBlock');
            $cTemplate->addMethod('public', 'create', ['array $args', 'array $options = array()'], AnnotatedBlock::apply($elements, $codegenSettings));
        }


        $arguments = new ArgumentArray();
        $loadByPrimaryKeyQuery = $schema->newFindByPrimaryKeyQuery();
        $loadByPrimaryKeySql = $loadByPrimaryKeyQuery->toSql($readQueryDriver, $arguments);
        $cTemplate->addConst('FIND_BY_PRIMARY_KEY_SQL', $loadByPrimaryKeySql);


        $cTemplate->addMethod('public', 'loadByPrimaryKey', ['$pkId'], function() use ($schema) {
            return [
                "if (!\$this->loadStm) {",
                "   \$this->loadStm = \$this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);",
                "   \$this->loadStm->setFetchMode(PDO::FETCH_CLASS, '{$schema->getModelClass()}');",
                "}",
                "return static::_stmFetchOne(\$this->loadStm, [\$pkId]);",
            ];
        });

        foreach ($schema->getColumns() as $column) {
            if (!$column->findable) {
                continue;
            }
            $columnName = $column->name;
            $findMethodName = 'loadBy'.ucfirst(Inflector::camelize($columnName));
            $propertyName = $findMethodName . 'Stm';
            $cTemplate->addProtectedProperty($propertyName);

            $query = $schema->newSelectQuery();
            $query->where()->equal($columnName, new Bind($columnName));
            $query->limit(1);
            $sql = $query->toSql($readQueryDriver, new ArgumentArray);

            $constName = 'LOAD_BY_' . strtoupper($columnName) . '_SQL';
            $cTemplate->addConst($constName, $sql);

            $cTemplate->addMethod('public', $findMethodName, ['$value'], function() use($schema, $columnName, $propertyName, $constName) {
                return PDOStatementCodeGen::generateFetchOne(
                    $propertyName,
                    $constName,
                    $schema->getModelClass(),
                    "[':{$columnName}' => \$value ]");
            });
        }



        $arguments = new ArgumentArray();
        $deleteQuery = new DeleteQuery();
        $deleteQuery->delete($schema->getTable());
        $deleteQuery->where()->equal($schema->primaryKey,  new ParamMarker($schema->primaryKey));
        $deleteQuery->limit(1);
        $deleteByPrimaryKeySql = $deleteQuery->toSql($writeQueryDriver, $arguments);
        $cTemplate->addConst('DELETE_BY_PRIMARY_KEY_SQL', $deleteByPrimaryKeySql);
        $cTemplate->addMethod('public',
            'deleteByPrimaryKey',
            ['$pkId'], 
            PDOStatementCodeGen::generateExecute('deleteStm', 'DELETE_BY_PRIMARY_KEY_SQL', "[\$pkId]")
        );

        foreach ($schema->getRelations() as $relKey => $rel) {


            switch($rel['type']) {
                case Relationship::HAS_ONE:
                case Relationship::BELONGS_TO:
                    $relName = ucfirst(Inflector::camelize($relKey));
                    $methodName = 'fetch'. $relName. 'Of';
                    $propertyName = 'fetch'. $relName .'Stm';
                    $cTemplate->addProtectedProperty($propertyName);

                    $foreignSchema = $rel->newForeignSchema();
                    $query = $foreignSchema->newSelectQuery(); // foreign key
                    $query->where()->equal($rel->getForeignColumn(), new ParamMarker());
                    $query->limit(1); // Since it's a belongs to relationship, there is only one record.
                    $sql = $query->toSql($readQueryDriver, new ArgumentArray);

                    $constName = "FETCH_" . strtoupper($relKey) . "_SQL";
                    $cTemplate->addConst($constName, $sql);

                    $selfColumn    = $rel->getSelfColumn();
                    $cTemplate->addMethod('public', $methodName, ['BaseModel $record'],
                        PDOStatementCodeGen::generateFetchOne(
                            $propertyName,
                            $constName,
                            $foreignSchema->getModelClass(), "[\$record->$selfColumn]"));
                    break;
                case Relationship::HAS_MANY:
                    $relName = ucfirst(Inflector::camelize($relKey));
                    $methodName = 'fetch'. $relName. 'Of';
                    $propertyName = 'fetch'. $relName .'Stm';
                    $cTemplate->addProtectedProperty($propertyName);

                    $foreignSchema = $rel->newForeignSchema();
                    $query = $foreignSchema->newSelectQuery(); // foreign key
                    $query->where()->equal($rel->getForeignColumn(), new ParamMarker());
                    $sql = $query->toSql($readQueryDriver, new ArgumentArray);

                    $constName = "FETCH_" . strtoupper($relKey) . "_SQL";
                    $cTemplate->addConst($constName, $sql);

                    $selfColumn = $rel->getSelfColumn();
                    $cTemplate->addMethod('public', $methodName, ['BaseModel $record'],
                        PDOStatementCodeGen::generateFetchAll(
                            $propertyName,
                            $constName,
                            $foreignSchema->getModelClass(), "[\$record->$selfColumn]"));


                    break;
            }
        }



        $cTemplate->extendClass('\\'.$baseClass);
        return $cTemplate;
    }
}
