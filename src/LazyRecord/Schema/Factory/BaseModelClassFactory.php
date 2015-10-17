<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\ConnectionManager;
use Doctrine\Common\Inflector\Inflector;
use ReflectionClass;


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
        $cTemplate->useClass('SQLBuilder\\Bind');
        $cTemplate->useClass('SQLBuilder\\ArgumentArray');
        $cTemplate->useClass('PDO');
        $cTemplate->useClass('SQLBuilder\\Universal\\Query\\InsertQuery');



        $cTemplate->addConsts(array(
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'COLLECTION_CLASS'   => $schema->getCollectionClass(),
            'MODEL_CLASS'        => $schema->getModelClass(),
            'TABLE'              => $schema->getTable(),
            'READ_SOURCE_ID'     => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID'    => $schema->getWriteSourceId(),
            'PRIMARY_KEY'        => $schema->primaryKey,
        ));

        $cTemplate->addProtectedProperty('table', $schema->getTable());
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

        $schemaReflection = new ReflectionClass($schema);
        $schemaDocComment = $schemaReflection->getDocComment();
        $foundAnnotation = strpos($schemaDocComment, '@codegen') !== FALSE;


        // TODO: apply settings from schema...
        $codegenSettings = [
            'filterColumn' => true,
            'validateRequire' => true,
            'validateColumn' => true,
            'typeConstraint' => true,
        ];
        preg_match_all('/@codegen (\w+)(?:\s*=\s*(\S+))?$/m', $schemaDocComment, $allMatches);
        for ($i = 0; $i < count($allMatches[0]); $i++) {
            $key = $allMatches[1][$i];
            $value = $allMatches[2][$i];

            if ($value === "") {
                $value = true;
            } else {
                if (strcasecmp($value, "true") == 0 || strcasecmp($value, "false") == 0) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else if (preg_match('/^\d+$/', $value)) {
                    $value = intval($value);
                }
            }
            $codegenSettings[$key] = $value;
        }

        if ($codegenSettings['validateColumn']) {
            $codegenSettings['handleValidationError'] = true;
        }

        if ($foundAnnotation || !empty($codegenSettings)) {
            $reflectionModel = new ReflectionClass('LazyRecord\\BaseModel');
            $createMethod = $reflectionModel->getMethod('create');
            $methodFile = $createMethod->getFilename();
            $startLine = $createMethod->getStartLine();
            $endLine = $createMethod->getEndLine();
            $lines = file($methodFile);
            $methodLines = array_slice($lines, $startLine + 1, $endLine - $startLine - 2); // exclude '{', '}'

            $codegenBlock = array();

            $codegenBlock['validateRequire'] =<<<'CODE'

            if ($c->required && array_key_exists($n, $args) && $args[$n] === null) {
                return $this->reportError("Value of $n is required.");
            }
CODE;


            $codegenBlock['typeConstraint'] =<<<'CODE'

            if ($c->typeConstraint && ($val !== null && ! is_array($val) && ! $val instanceof Raw)) {
                if (false === $c->checkTypeConstraint($val)) {
                    return $this->reportError("{$val} is not " . $c->isa . " type");
                }
            } else if ($val !== NULL && !is_array($val) && !$val instanceof Raw) {
                $val = $c->typeCasting($val);
            }
CODE;

            $codegenBlock['filterColumn'] =<<<'CODE'

            if ($c->filter || $c->canonicalizer) {
                $val = $c->canonicalizeValue($val, $this, $args);
            }
CODE;


            $codegenBlock['currentUserCan'] =<<<'CODE'

        if (! $this->currentUserCan($this->getCurrentUser(), 'create', $args )) {
            return $this->reportError( _('Permission denied. Can not create record.') , array( 
                'args' => $args,
            ));
        }
CODE;

            $codegenBlock['validateColumn'] =<<<'CODE'
            if ($validationResult = $this->_validateColumn($c,$val,$args)) {
                $validationResults[$n] = $validationResult;
                if (!$validationResult['valid']) {
                    $validationError = true;
                }
            }
CODE;

            $codegenBlock['handleValidationError'] =<<<'CODE'
        if ($validationError) {
            return $this->reportError("Validation failed.", array( 
                'validations' => $validationResults,
            ));
        }
CODE;

            $overrideCreateMethod = $cTemplate->addMethod('public', 'create', ['array $args', 'array $options = array()']);
            $overrideBlock = $overrideCreateMethod->getBlock();
            foreach ($methodLines as $line) {
                $line = rtrim($line);
                if (preg_match('/@codegen (\w+)/',$line, $matches)) {
                    if (isset($codegenSettings[$matches[1]]) && $codegenSettings[$matches[1]]) {
                        $overrideBlock[] = $codegenBlock[$matches[1]];
                    } else {
                        $overrideBlock[] = $line;
                    }
                } else {
                    $overrideBlock[] = $line;
                }
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
            $block[] = 'if (false === ($this->_data = $this->_preparedFindStms[' . var_export($columnName, true ) . ']->fetch(PDO::FETCH_ASSOC)) ) {';
            $block[] = '    return $this->reportError("Record not found", [';
            $block[] = '        "sql" => ' . var_export($findByColumnSql, true) . ',';
            $block[] = '    ]);';
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

