<?php

namespace Maghead\Generator\Schema;

use ReflectionClass;
use ReflectionMethod;
use InvalidArgumentException;

use Maghead\Schema\DeclareSchema;
use Maghead\Exception\SchemaRelatedException;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Manager\DataSourceManager;
use Doctrine\Common\Inflector\Inflector;

use Maghead\Generator\PDOStatementGenerator;
use Maghead\Generator\AccessorGenerator;

use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Bind;
use SQLBuilder\ParamMarker;
use SQLBuilder\ArgumentArray;

use ClassTemplate\ClassFile;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Expr\ConcatExpr;
use CodeGen\Raw;

class PrimaryKeyColumnMissingException extends SchemaRelatedException { }

/**
 * Base Model class generator.
 *
 * Some rules for generating code:
 *
 * - Mutable values should be generated as propertes.
 * - Immutable values should be generated as constants.
 */
class BaseModelClassGenerator
{
    public static function create(DeclareSchema $schema, $baseClass)
    {
        // get data source ids
        $readFrom = $schema->getReadSourceId();
        $writeTo  = $schema->getWriteSourceId();

        $primaryKey = $schema->primaryKey;
        if (!$primaryKey) {
            throw new PrimaryKeyColumnMissingException($schema, "PrimaryKey is required to be defined in the schema.");
        }

        $cTemplate = new ClassFile($schema->getBaseModelClass());
        $cTemplate->extendClass('\\'.$baseClass);

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
        $cTemplate->useClass('Maghead\\Inflator');
        $cTemplate->useClass('SQLBuilder\\Bind');
        $cTemplate->useClass('SQLBuilder\\ArgumentArray');
        $cTemplate->useClass('DateTime');

        $cTemplate->addConsts(array(
            'SCHEMA_CLASS'       => get_class($schema),
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'COLLECTION_CLASS'   => $schema->getCollectionClass(),
            'MODEL_CLASS'        => $schema->getModelClass(),
            'REPO_CLASS'         => $schema->getBaseRepoClass(),
            'TABLE'              => $schema->getTable(),
            'READ_SOURCE_ID'     => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID'    => $schema->getWriteSourceId(),
            'PRIMARY_KEY'        => $schema->primaryKey,
            'TABLE_ALIAS'        => 'm',
        ));

        $cTemplate->addConst('GLOBAL_PRIMARY_KEY', $schema->findGlobalPrimaryKey());
        $cTemplate->addConst('LOCAL_PRIMARY_KEY', $schema->findLocalPrimaryKey());

        // Sharding related constants
        $cTemplate->addConst('SHARD_MAPPING_ID', $schema->shardMapping);
        $cTemplate->addConst('GLOBAL_TABLE', $schema->globalTable);

        // TODO: can be removed now.
        $cTemplate->addProtectedProperty('table', $schema->getTable());

        $cTemplate->addStaticVar('column_names', $schema->getColumnNames());
        $cTemplate->addStaticVar('mixin_classes', array_reverse($schema->getMixinSchemaClasses()));

        $cTemplate->addStaticMethod('public', 'getSchema', [], function () use ($schema) {
            return [
                "static \$schema;",
                "if (\$schema) {",
                "   return \$schema;",
                "}",
                "return \$schema = new \\{$schema->getSchemaProxyClass()};",
            ];
        });

        if ($traitClasses = $schema->getModelTraitClasses()) {
            foreach ($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }

        $cTemplate->addStaticMethod('public', 'createRepo', ['$write', '$read'], function () use ($schema) {
            return "return new \\{$schema->getBaseRepoClass()}(\$write, \$read);";
        });



        // interfaces
        if ($ifs = $schema->getModelInterfaces()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }




        $cTemplate->addMethod('public', 'getKeyName', [], function () use ($primaryKey) {
            return "return " . var_export($primaryKey, true) . ';' ;
        });

        $cTemplate->addMethod('public', 'getKey', [], function () use ($primaryKey) {
            return
                "return \$this->{$primaryKey};"
            ;
        });

        $cTemplate->addMethod('public', 'hasKey', [], function () use ($primaryKey) {
            return
                "return isset(\$this->{$primaryKey});"
            ;
        });

        $cTemplate->addMethod('public', 'setKey', ['$key'], function () use ($primaryKey) {
            return
                "return \$this->{$primaryKey} = \$key;"
            ;
        });


        $cTemplate->addMethod('public', 'removeLocalPrimaryKey', [], function () use ($schema) {
            if ($key = $schema->findLocalPrimaryKey()) {
                return "\$this->$key = null;";
            }
            return [];
        });

        $cTemplate->addMethod('public', 'removeGlobalPrimaryKey', [], function () use ($schema) {
            if ($key = $schema->findGlobalPrimaryKey()) {
                return "\$this->$key = null;";
            }
            return [];
        });




        // Create column accessor
        $properties = [];
        foreach ($schema->getColumns(false) as $columnName => $column) {
            $propertyName = Inflector::camelize($columnName);
            $properties[] = [$columnName, $propertyName];

            $cTemplate->addPublicProperty($columnName, null);


            if ($schema->enableColumnAccessors) {
                $booleanAccessor = false;
                if (preg_match('/^is[A-Z]/', $propertyName)) {
                    $booleanAccessor = true;
                    $accessorMethodName = $propertyName;
                } elseif ($column->isa === "bool") {
                    // for column names like "is_confirmed", don't prepend another "is" prefix to the accessor name.
                    $booleanAccessor = true;
                    $accessorMethodName = 'is'.ucfirst($propertyName);
                } else {
                    $accessorMethodName = 'get'.ucfirst($propertyName);
                }
                AccessorGenerator::generateGetterAccessor($cTemplate, $column, $accessorMethodName, $propertyName);

                /*
                if (!$booleanAccessor) {
                    AccessorGenerator::generateSetterAccessor($cTemplate, $column, 'set'.ucfirst($propertyName), $propertyName);
                }
                */
            }

            // Generate findable proxy methods
            if ($column->findable) {
                $findMethodName = 'findBy'.ucfirst(Inflector::camelize($columnName));
                $cTemplate->addMethod('public', $findMethodName, ['$value'], function () use ($findMethodName) {
                    // Call BaseRepo methods on masterRepo
                    return ["return static::masterRepo()->{$findMethodName}(\$value);"];
                });
            }
        }

        $cTemplate->addMethod('public', 'getData', [], function () use ($properties) {
            return
                'return [' . join(", ", array_map(function ($p) {
                    list($columnName, $propertyName) = $p;
                    return "\"$columnName\" => \$this->{$columnName}";
                }, $properties)) . '];'
            ;
        });

        $cTemplate->addMethod('public', 'setData', ['array $data'], function () use ($properties) {
            return array_map(function ($p) {
                list($columnName, $propertyName) = $p;
                return "if (array_key_exists(\"{$columnName}\", \$data)) { \$this->{$columnName} = \$data[\"{$columnName}\"]; }";
            }, $properties);
        });

        $cTemplate->addMethod('public', 'clear', [], function () use ($properties) {
            return array_map(function ($p) {
                list($columnName, $propertyName) = $p;
                return "\$this->{$columnName} = NULL;";
            }, $properties);
        });


        foreach ($schema->getRelations() as $relKey => $rel) {
            switch ($rel['type']) {
                case Relationship::HAS_ONE:
                case Relationship::HAS_MANY:
                case Relationship::BELONGS_TO:
                $relName = ucfirst(Inflector::camelize($relKey));
                $methodName = 'fetch'. $relName;
                $repoMethodName = 'fetch'. $relName . 'Of';
                $cTemplate->addMethod('public', $methodName, [],
                    "return static::masterRepo()->{$repoMethodName}(\$this);");
                break;
            }


            $relName = ucfirst(Inflector::camelize($relKey));
            $methodName = 'get'. $relName;

            switch ($rel['type']) {
                case Relationship::HAS_MANY:

                $foreignSchema = $rel->newForeignSchema();
                $foreignCollectionClass = $foreignSchema->getCollectionClass();

                $foreignColumn = $rel->getForeignColumn();
                $selfColumn = $rel->getSelfColumn();

                $cTemplate->addMethod('public', $methodName, [], function () use ($foreignCollectionClass, $foreignColumn, $selfColumn) {
                    return [
                        "\$collection = new \\{$foreignCollectionClass};",
                        "\$collection->where()->equal(\"{$foreignColumn}\", \$this->{$selfColumn});",
                        "\$collection->setPresetVars([ \"{$foreignColumn}\" => \$this->{$selfColumn} ]);",
                        "return \$collection;",
                    ];
                });

                break;

                case Relationship::MANY_TO_MANY:



                // assemble the join query with the collection class string
                $cTemplate->addMethod('public', $methodName, [], function () use ($schema, $relName, $relKey, $rel) {
                    $junctionRelKey = $rel['relation_junction'];
                    $junctionRel = $schema->getRelation($junctionRelKey);
                    if (!$junctionRel) {
                        throw new InvalidArgumentException("Junction relationship of many-to-many $junctionRelKey is undefined.");
                    }
                    $junctionSchema = $junctionRel->newForeignSchema();

                    $foreignRelKey = $rel['relation_foreign'];
                    $foreignRel = $junctionSchema->getRelation($foreignRelKey);
                    if (!$foreignRel) {
                        throw new InvalidArgumentException("Foreign relationship of many-to-many $foreignRelKey is undefined.");
                    }
                    $foreignSchema = $foreignRel->newForeignSchema();
                    $targetCollectionClass = $foreignSchema->getCollectionClass();

                    $selfRefColumn = $foreignRel->getForeignColumn();

                    // Join the junction table, generate some sql query like this:
                    //      SELECT * from books m LEFT JOIN author_books j on (j.book_id = m.id)
                    //      WHERE j.author_id = :author_id
                    return [
                        "\$collection = new \\{$targetCollectionClass};",
                        "\$collection->joinTable('{$junctionSchema->getTable()}', 'j', 'INNER')",
                        "   ->on(\"j.{$foreignRel->getSelfColumn()} = {\$collection->getAlias()}.{$foreignRel->getForeignColumn()}\");",
                        // " ->on()->equal('j.{$foreignRel->getSelfColumn()}', [\$collection->getAlias() . '.{$foreignRel->getForeignColumn()}']);",
                        "\$collection->where()->equal('j.{$junctionRel->getForeignColumn()}', \$this->{$selfRefColumn});",
                        "\$parent = \$this;",
                        "\$collection->setAfterCreate(function(\$record, \$args) use (\$parent) {",
                        "   \$a = [",
                        "      '{$foreignRel->getSelfColumn()}' => \$record->get(\"{$foreignRel->getForeignColumn()}\"),",
                        "      '{$junctionRel['foreign_column']}' => \$parent->{$selfRefColumn},",
                        "   ];",
                        "   if (isset(\$args['{$junctionRelKey}'])) {",
                        "      \$a = array_merge(\$args['{$junctionRelKey}'], \$a);",
                        "   }",
                        "   return \\{$junctionSchema->getModelClass()}::createAndLoad(\$a);",
                        "});",
                        "return \$collection;",
                    ];
                });
                break;
            }
        }

        return $cTemplate;
    }
}
