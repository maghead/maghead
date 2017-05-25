<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Manager\DataSourceManager;
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Runtime\Bootstrap;
use Doctrine\Common\Inflector\Inflector;
use ReflectionClass;

// used for SQL generator
use Magsql\Universal\Query\SelectQuery;
use Magsql\Universal\Query\DeleteQuery;
use Magsql\Bind;
use Magsql\ParamMarker;
use Magsql\ArgumentArray;

use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Expr\ConcatExpr;
use CodeGen\Raw;

use Maghead\Generator\AnnotatedBlock;
use Maghead\Generator\PDOStatementGenerator;
use Maghead\Generator\CodeGenSettingsParser;
use Maghead\Generator\MethodBlockParser;

use Maghead\Runtime\Config\FileConfigLoader;

/**
 * Base Repo class generator.
 */
class BaseRepoClassGenerator
{
    public static function create(DeclareSchema $schema, $baseClass)
    {
        $readFrom = $schema->getReadSourceId();
        $writeTo  = $schema->getWriteSourceId();

        $readConnection = DataSourceManager::getInstance()->getConnection($readFrom);
        $readQueryDriver = $readConnection->getQueryDriver();

        $writeConnection = DataSourceManager::getInstance()->getConnection($writeTo);
        $writeQueryDriver = $writeConnection->getQueryDriver();

        $cTemplate = clone $schema->classes->baseRepo;

        $cTemplate->useClass('Maghead\\Schema\\SchemaLoader');
        $cTemplate->useClass('Maghead\\Runtime\\Result');
        $cTemplate->useClass('Maghead\\Runtime\\Model');
        $cTemplate->useClass('Maghead\\Runtime\\Inflator');
        $cTemplate->useClass('Magsql\\Bind');
        $cTemplate->useClass('Magsql\\ArgumentArray');
        $cTemplate->useClass('PDO');
        $cTemplate->useClass('Magsql\\Universal\\Query\\InsertQuery');

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


        $config = Bootstrap::getConfig();

        // Sharding related constants
        // If sharding is not enabled, don't throw exception.
        if (isset($config['sharding'])) {
            $cTemplate->addConst('SHARD_MAPPING_ID', $schema->shardMapping);
            $cTemplate->addConst('GLOBAL_TABLE', $schema->globalTable);
            $cTemplate->addConst('SHARD_KEY', $schema->getShardKey());
        }

        $cTemplate->addProtectedProperty('table', $schema->getTable());
        $cTemplate->addStaticVar('columnNames', $schema->getColumnNames());
        $cTemplate->addStaticVar('columnHash', array_fill_keys($schema->getColumnNames(), 1));
        $cTemplate->addStaticVar('mixinClasses', array_reverse($schema->getMixinSchemaClasses()));

        $cTemplate->addMethod('public', 'free', [], function () use ($schema) {
            return [
                '$this->loadStm = null;',
                '$this->deleteStm = null;',
            ];
        });

        $cTemplate->addStaticMethod('public', 'getSchema', [], function () use ($schema) {
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
            $reflectionRepo = new ReflectionClass('Maghead\\Runtime\\Repo');
            $createMethod = $reflectionRepo->getMethod('create');
            $elements = MethodBlockParser::parseElements($createMethod, 'codegenBlock');
            $cTemplate->addMethod('public', 'create', ['array $args', 'array $options = array()'], AnnotatedBlock::apply($elements, $codegenSettings));
        }

        if ($findByGlobalPrimaryKeyQuery = $schema->newFindByGlobalPrimaryKeyQuery()) {
            $findByGlobalPrimaryKeySql = $findByGlobalPrimaryKeyQuery->toSql($readQueryDriver, new ArgumentArray());
            $cTemplate->addConst('FIND_BY_GLOBAL_PRIMARY_KEY_SQL', $findByGlobalPrimaryKeySql);
            $cTemplate->addProtectedProperty('findGlobalPrimaryKeyStm');
            $cTemplate->addMethod('public', 'findByGlobalPrimaryKey', ['$pkId'], function () use ($schema) {
                return [
                    "if (!\$this->findGlobalPrimaryKeyStm) {",
                    "   \$this->findGlobalPrimaryKeyStm = \$this->read->prepare(self::FIND_BY_GLOBAL_PRIMARY_KEY_SQL);",
                    "   \$this->findGlobalPrimaryKeyStm->setFetchMode(PDO::FETCH_CLASS, '{$schema->getModelClass()}', [\$this]);",
                    "}",
                    "\$this->findGlobalPrimaryKeyStm->execute([ \$pkId ]);",
                    "\$obj = \$this->findGlobalPrimaryKeyStm->fetch();",
                    "\$this->findGlobalPrimaryKeyStm->closeCursor();",
                    "return \$obj;",
                ];
            });
        }


        $findByPrimaryKeyQuery = $schema->newFindByPrimaryKeyQuery();
        $findByPrimaryKeySql = $findByPrimaryKeyQuery->toSql($readQueryDriver, new ArgumentArray());
        $cTemplate->addConst('FIND_BY_PRIMARY_KEY_SQL', $findByPrimaryKeySql);
        $cTemplate->addProtectedProperty('loadStm');
        $cTemplate->addMethod('public', 'findByPrimaryKey', ['$pkId'], function () use ($schema) {
            return [
                "if (!\$this->loadStm) {",
                "   \$this->loadStm = \$this->read->prepare(self::FIND_BY_PRIMARY_KEY_SQL);",
                "   \$this->loadStm->setFetchMode(PDO::FETCH_CLASS, '{$schema->getModelClass()}', [\$this]);",
                "}",
                "\$this->loadStm->execute([ \$pkId ]);",
                "\$obj = \$this->loadStm->fetch();",
                "\$this->loadStm->closeCursor();",
                "return \$obj;",
            ];
        });

        foreach ($schema->getColumns() as $column) {
            if (!$column->findable) {
                continue;
            }
            $columnName = $column->name;
            $findMethodName = 'findBy'.ucfirst(Inflector::camelize($columnName));
            $propertyName = $findMethodName . 'Stm';
            $cTemplate->addProtectedProperty($propertyName);

            $query = $schema->newSelectQuery();
            $query->where()->equal($columnName, new Bind($columnName));
            $query->limit(1);
            $sql = $query->toSql($readQueryDriver, new ArgumentArray);

            $constName = 'LOAD_BY_' . strtoupper($columnName) . '_SQL';
            $cTemplate->addConst($constName, $sql);

            $cTemplate->addMethod('public', $findMethodName, ['$value'], function () use ($schema, $columnName, $propertyName, $constName) {
                return PDOStatementGenerator::generateFetchOne(
                    $propertyName,
                    $constName,
                    $schema->getModelClass(),
                    "[':{$columnName}' => \$value ]");
            });
        }

        // TODO: can be static method
        $cTemplate->addMethod('protected', 'unsetImmutableArgs', ['$args'], function () use ($schema) {
            $immutableColumns = array_filter($schema->getColumns(false), function ($c) {
                return $c->immutable;
            });

            $lines = array_map(function ($c) {
                return "unset(\$args[\"{$c->name}\"]);";
            }, $immutableColumns);

            $lines[] = "return \$args;";

            return $lines;
        });


        $arguments = new ArgumentArray();
        $deleteQuery = $schema->newDeleteByPrimaryKeyQuery();
        $deleteByPrimaryKeySql = $deleteQuery->toSql($writeQueryDriver, $arguments);
        $cTemplate->addConst('DELETE_BY_PRIMARY_KEY_SQL', $deleteByPrimaryKeySql);
        $cTemplate->addMethod('public',
            'deleteByPrimaryKey',
            ['$pkId'],
            PDOStatementGenerator::generateExecute('deleteStm', 'DELETE_BY_PRIMARY_KEY_SQL', "[\$pkId]")
        );

        foreach ($schema->getRelations() as $relKey => $rel) {
            $relName = ucfirst(Inflector::camelize($relKey));
            $methodName = "fetch{$relName}Of";
            $propertyName = "fetch{$relName}Stm";
            $constName = "FETCH_" . strtoupper($relKey) . "_SQL";

            switch ($rel['type']) {

                case Relationship::HAS_ONE:
                case Relationship::BELONGS_TO:
                    $foreignSchema = $rel->newForeignSchema();
                    $query = $foreignSchema->newSelectQuery(); // foreign key
                    $query->where()->equal($rel->getForeignColumn(), new ParamMarker());
                    $query->limit(1); // Since it's a belongs to relationship, there is only one record.
                    $sql = $query->toSql($readQueryDriver, new ArgumentArray);

                    $cTemplate->addConst($constName, $sql);
                    $cTemplate->addProtectedProperty($propertyName);

                    $selfColumn    = $rel->getSelfColumn();
                    $cTemplate->addMethod('public', $methodName, ['Model $record'],
                        PDOStatementGenerator::generateFetchOne(
                            $propertyName,
                            $constName,
                            $foreignSchema->getModelClass(), "[\$record->$selfColumn]"));
                    break;
                case Relationship::HAS_MANY:
                    $foreignSchema = $rel->newForeignSchema();
                    $query = $foreignSchema->newSelectQuery(); // foreign key
                    $query->where()->equal($rel->getForeignColumn(), new ParamMarker());
                    $sql = $query->toSql($readQueryDriver, new ArgumentArray);

                    $cTemplate->addConst($constName, $sql);
                    $cTemplate->addProtectedProperty($propertyName);

                    $selfColumn = $rel->getSelfColumn();
                    $cTemplate->addMethod('public', $methodName, ['Model $record'],
                        PDOStatementGenerator::generateFetchAll(
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
