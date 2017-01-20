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


class CodeBlock
{
    public $id;
    public $lines;
    public $range;

    public function __construct($id, array $lines = [], array $range = null) {
        $this->id = $id;
        $this->lines = $lines;
        $this->range = $range;
    }

    static public function apply($elements, array $settings)
    {
        $body = [];
        foreach ($elements as $el) {
            if ($el instanceof CodeBlock) {
                $blockId = $el->id;
                if (isset($settings[$blockId]) && isset($el->lines)) {
                    if ($settings[$blockId]) {
                        $body[] = $el->lines;
                    }
                }
            } else {
                $body[] = $el;
            }
        }
        return $body;
    }
}

class BlockGenerator
{
}

class MethodBlockParser
{

    /**
     * parseMethod doesn't return block mapping, it returns the lines and blocks in sequence.
     */
    static public function elements(ReflectionMethod $method)
    {
        $methodFile = $createMethod->getFilename();
        $startLine = $createMethod->getStartLine();
        $endLine = $createMethod->getEndLine();
        $lines = file($methodFile);
        $methodLines = array_slice($lines, $startLine + 1, $endLine - $startLine - 2); // exclude '{', '}'
        $blocks = [];
        for ($i = 0; $i < count($methodLines); ++$i) {
            $line = rtrim($methodLines[$i]);
            if (preg_match('/@codegenBlock (\w+)/', $line, $matches)) {
                $blockId = $matches[1];
                $block = new CodeBlock($blockId);
                for ($j = $i; $j < count($methodLines); ++$j) {
                    $line = rtrim($methodLines[$j]);
                    $block->lines[] = $line;
                    if (preg_match('/@codegenBlockEnd/', $line)) {
                        $block->range = [$i, $j];
                        $i = $j; // find the next block
                        break;
                    }
                }
                $blocks[] = $block;
            } else {
                $blocks[] = $line;
            }
        }
        return $blocks;

    }

    static public function getBlocks(ReflectionMethod $method)
    {
        $methodFile = $createMethod->getFilename();
        $startLine = $createMethod->getStartLine();
        $endLine = $createMethod->getEndLine();
        $lines = file($methodFile);
        $methodLines = array_slice($lines, $startLine + 1, $endLine - $startLine - 2); // exclude '{', '}'
        $blocks = [];
        for ($i = 0; $i < count($methodLines); ++$i) {
            $line = rtrim($methodLines[$i]);
            if (preg_match('/@codegenBlock (\w+)/', $line, $matches)) {
                $blockId = $matches[1];
                for ($j = $i; $j < count($methodLines); ++$j) {
                    $line = rtrim($methodLines[$j]);
                    $blocks[$blockId]['lines'][] = $line;
                    if (preg_match('/@codegenBlockEnd/', $line)) {
                        $blocks[$blockId]['range'] = [$i, $j];
                        $i = $j; // find the next block
                        break;
                    }
                }
            }
        }
        return $blocks;
    }



}

/**
 * Base Model class generator.
 *
 * Some rules for generating code:
 *
 * - Mutable values should be generated as propertes.
 * - Immutable values should be generated as constants.
 */
class BaseModelClassFactory
{
    public static function create(DeclareSchema $schema, $baseClass)
    {
        $cTemplate = new ClassFile($schema->getBaseModelClass());

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
            'TABLE_ALIAS'              => 'm',
        ));

        $cTemplate->addProtectedProperty('table', $schema->getTable());
        $cTemplate->addPublicProperty('readSourceId', $schema->getReadSourceId() ?: 'default');
        $cTemplate->addPublicProperty('writeSourceId', $schema->getWriteSourceId() ?: 'default');


        $cTemplate->addStaticVar('column_names',  $schema->getColumnNames());
        $cTemplate->addStaticVar('column_hash',  array_fill_keys($schema->getColumnNames(), 1));
        $cTemplate->addStaticVar('mixin_classes', array_reverse($schema->getMixinSchemaClasses()));

        $cTemplate->addStaticMethod('public', 'getSchema', [], function() use ($schema) {
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

        $schemaReflection = new ReflectionClass($schema);
        $schemaDocComment = $schemaReflection->getDocComment();

        // TODO: apply settings from schema...
        $codegenSettings = [];
        preg_match_all('/@codegen (\w+)(?:\s*=\s*(\S+))?$/m', $schemaDocComment, $allMatches);
        for ($i = 0; $i < count($allMatches[0]); ++$i) {
            $key = $allMatches[1][$i];
            $value = $allMatches[2][$i];

            if ($value === '') {
                $value = true;
            } else {
                if (strcasecmp($value, 'true') == 0 || strcasecmp($value, 'false') == 0) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } elseif (preg_match('/^\d+$/', $value)) {
                    $value = intval($value);
                }
            }
            $codegenSettings[$key] = $value;
        }

        /*
        if ($codegenSettings['validateColumn']) {
            $codegenSettings['handleValidationError'] = true;
        }
        */
        if (!empty($codegenSettings)) {
            $reflectionModel = new ReflectionClass('LazyRecord\\BaseModel');
            $createMethod = $reflectionModel->getMethod('create');
            $elements = MethodBlockParser::elements($method);
            $cTemplate->addMethod('public', 'create', ['array $args', 'array $options = array()'], CodeBlock::apply($elements, $codegenSettings));
        }

        $primaryKey = $schema->primaryKey;
        $readFrom = $schema->getReadSourceId();
        $writeTo  = $schema->getWriteSourceId();
        $readConnection = ConnectionManager::getInstance()->getConnection($readFrom);
        $writeConnection = ConnectionManager::getInstance()->getConnection($writeTo);
        $readQueryDriver = $readConnection->getQueryDriver();
        $writeQueryDriver = $writeConnection->getQueryDriver();

        $cTemplate->addStaticMethod('protected', 'createRepo', ['$write', '$read'], function() use ($schema) {
            return "return new \\{$schema->getRepoClass()}(\$write, \$read);";
        });

        $arguments = new ArgumentArray();
        $deleteQuery = new DeleteQuery();
        $deleteQuery->delete($schema->getTable());
        $deleteQuery->where()->equal($schema->primaryKey,  new ParamMarker($schema->primaryKey));
        $deleteQuery->limit(1);
        $deleteByPrimaryKeySql = $deleteQuery->toSql($writeQueryDriver, $arguments);
        $cTemplate->addConst('DELETE_BY_PRIMARY_KEY_SQL', $deleteByPrimaryKeySql);

        $cTemplate->addStaticMethod('public', 'deleteByPrimaryKey', ['$pkId'], function() use ($deleteByPrimaryKeySql, $schema) {
            return [
                    "\$record = new static;",
                    "\$conn = \$record->getWriteConnection();",
                    "\$stm = \$conn->prepare(self::DELETE_BY_PRIMARY_KEY_SQL);",
                    "return \$stm->execute([\$pkId]);",
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

        // interfaces
        if ($ifs = $schema->getModelInterfaces()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }

        // Create column accessor
        $properties = [];
        foreach ($schema->getColumns(false) as $columnName => $column) {
            $propertyName = Inflector::camelize($columnName);
            $properties[] = [$columnName, $propertyName];

            $cTemplate->addPublicProperty($columnName, NULL);

            if ($schema->enableColumnAccessors) {

                if (preg_match('/^is[A-Z]/', $propertyName)) {
                    $accessorMethodName = $propertyName;
                } else if ($column->isa === "bool") {
                    // for column names like "is_confirmed", don't prepend another "is" prefix to the accessor name.
                    $accessorMethodName = 'is'.ucfirst($propertyName);
                } else {
                    $accessorMethodName = 'get'.ucfirst($propertyName);
                }

                $cTemplate->addMethod('public', $accessorMethodName, [], function() use ($column, $columnName, $propertyName) {
                    if ($column->get('inflator')) {
                        return [
                            "if (\$c = \$this->getSchema()->getColumn(\"$columnName\")) {",
                            "     return \$c->inflate(\$this->$columnName, \$this);",
                            "}",
                            "return \$this->$columnName;",
                        ];
                    }
                    if ($column->isa === "int") {
                        return ["return intval(\$this->$columnName);"];
                    } else if ($column->isa === "str") {
                        return ["return \$this->$columnName;"];
                    } else if ($column->isa === "bool") {
                        return [
                            "\$value = \$this->$columnName;",
                            "if (\$value === '' || \$value === null) {",
                            "   return null;",
                            "}",
                            "return boolval(\$value);",
                        ];
                    } else if ($column->isa === "float") {
                        return ["return floatval(\$this->$columnName);"];
                    } else if ($column->isa === "json") {
                        return ["return json_decode(\$this->$columnName);"];
                    }
                    return ["return Inflator::inflate(\$this->$columnName, '{$column->isa}');"];
                });
            }
        }

        $cTemplate->addMethod('public', 'getKeyName', [], function() use ($primaryKey) {
            return
                "return " . var_export($primaryKey, true) . ';'
            ;
        });

        $cTemplate->addMethod('public', 'getKey', [], function() use ($primaryKey) {
            return 
                "return \$this->$primaryKey;"
            ;
        });

        $cTemplate->addMethod('public', 'hasKey', [], function() use ($primaryKey) {
            return 
                "return isset(\$this->$primaryKey);"
            ;
        });

        $cTemplate->addMethod('public', 'setKey', ['$key'], function() use ($primaryKey) {
            return 
                "return \$this->$primaryKey = \$key;"
            ;
        });

        $cTemplate->addMethod('public', 'getData', [], function() use ($properties) {
            return 
                'return [' . join(", ", array_map(function($p) {
                    list($columnName, $propertyName) = $p;
                    return "\"$columnName\" => \$this->$columnName";
                }, $properties)) . '];'
            ;
        });

        $cTemplate->addMethod('public', 'setData', ['array $data'], function() use ($properties) {
            return array_map(function($p) {
                    list($columnName, $propertyName) = $p;
                    return "if (array_key_exists(\"$columnName\", \$data)) { \$this->$columnName = \$data[\"$columnName\"]; }";
                }, $properties);
        });

        $cTemplate->addMethod('public', 'clear', [], function() use ($properties) {
            return array_map(function($p) {
                    list($columnName, $propertyName) = $p;
                    return "\$this->$columnName = NULL;";
                }, $properties);
        });

        return $cTemplate;
    }
}
