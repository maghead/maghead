<?php

namespace LazyRecord\Migration;

use RuntimeException;
use ReflectionClass;
use CLIFramework\Logger;
use LazyRecord\Schema;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\Comparator;
use ClassTemplate\ClassFile;
use CodeGen\Expr\MethodCallExpr;
use CodeGen\Statement\Statement;
use CodeGen\Raw;
use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\ArgumentArray;
use Doctrine\Common\Inflector\Inflector;

class MigrationGenerator
{
    public $logger;

    public $migrationDir;

    public function __construct(Logger $logger, $migrationDir)
    {
        $this->migrationDir = $migrationDir;
        if (!file_exists($this->migrationDir)) {
            mkdir($this->migrationDir, 0755, true);
        }
        $this->logger = $logger;
    }

    /**
     * Returns code template directory.
     */
    protected function getTemplateDirs()
    {
        $refl = new ReflectionClass('LazyRecord\Schema\SchemaGenerator');
        $path = $refl->getFilename();

        return dirname($path).DIRECTORY_SEPARATOR.'Templates';
    }

    public function generateFilename($taskName, $time = null)
    {
        $date = date('Y-m-d');
        if (is_integer($time)) {
            $date = date('Ymd', $time);
        } elseif (is_string($time)) {
            $date = $time;
        }
        $name = Inflector::tableize($taskName);

        return sprintf('%s_%s.php', $date, $taskName);
    }

    /**
     * @return ClassTemplate\ClassFile
     */
    public function createClassTemplate($taskName, $time = null)
    {
        if (!$time) {
            $time = time();
        } elseif (is_string($time)) {
            $time = strtotime($time);
        }
        $className = $taskName.'_'.$time;
        $template = new ClassFile($className);
        $template->useClass('SQLBuilder\\Universal\\Syntax\\Column');
        $template->useClass('SQLBuilder\\Universal\\Query\\AlterTableQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\CreateTableQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\UpdateTableQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\DeleteTableQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\InsertTableQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\CreateIndexQuery');
        $template->useClass('SQLBuilder\\Universal\\Query\\UnionQuery');
        $template->useClass('SQLBuilder\\Bind');
        $template->useClass('SQLBuilder\\ArgumentArray');
        $template->useClass('SQLBuilder\\Literal');
        $template->extendClass('LazyRecord\Migration\Migration');

        return $template;
    }

    public function generate($taskName, $time = null)
    {
        $template = $this->createClassTemplate($taskName, $time);
        $template->addMethod('public', 'upgrade', array(), '');
        $template->addMethod('public', 'downgrade', array(), '');
        $filename = $this->generateFilename($taskName, $time);
        $path = $this->migrationDir.DIRECTORY_SEPARATOR.$filename;
        if (false === file_put_contents($path, $template->render())) {
            throw new RuntimeException("Can't write template to $path");
        }

        return array($template->class->name, $path);
    }

    public function generateWithDiff($taskName, $dataSourceId, array $schemas, $time = null)
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connection = $connectionManager->getConnection($dataSourceId);
        $driver = $connectionManager->getQueryDriver($dataSourceId);

        $parser = TableParser::create($driver, $connection);
        $tableSchemas = $parser->getTableSchemaMap();

        $this->logger->info('Found '.count($schemas).' schemas to compare.');

        $template = $this->createClassTemplate($taskName, $time);
        $upgradeMethod = $template->addMethod('public', 'upgrade', array(), '');
        $downgradeMethod = $template->addMethod('public', 'downgrade', array(), '');

        $comparator = new Comparator($driver);

        // schema from runtime
        foreach ($schemas as $b) {
            $tableName = $b->getTable();
            $foundTable = isset($tableSchemas[ $tableName ]);
            if ($foundTable) {
                $a = $tableSchemas[$tableName]; // schema object, extracted from database.
                $diffs = $comparator->compare($a, $b);

                // generate alter table statement.
                foreach ($diffs as $diff) {
                    $alterTable = new AlterTableQuery($tableName);
                    switch ($diff->flag) {
                    case 'A':
                        $column = $diff->getAfterColumn();
                        $alterTable->addColumn($column);
                        /*
                        $this->logger->info(sprintf("'%s': add column %s", $tableName, $diff->name), 1);
                        $upcall = new MethodCallExpr('$this', 'addColumn', [$tableName, $column]);
                        $upgradeMethod[] = new Statement($upcall);
                        $downcall = new MethodCallExpr('$this', 'dropColumnByName', [$tableName, $diff->name]);
                        $downgradeMethod[] = new Statement($downcall);
                        */
                        break;
                    case 'M':
                        $afterColumn = $diff->getAfterColumn();
                        $beforeColumn = $diff->getBeforeColumn();
                        if (!$afterColumn || !$beforeColumn) {
                            throw new LogicException('afterColumn or beforeColumn is undefined.');
                        }
                        // Check primary key
                        if ($beforeColumn->primary != $afterColumn->primary) {
                            $alterTable->add()->primaryKey(['id']);
                        }
                        $alterTable->modifyColumn($afterColumn);
                        /*
                        if ($afterColumn = $diff->getAfterColumn()) {
                            $upcall = new MethodCallExpr('$this', 'modifyColumn', [$tableName, $afterColumn]);
                            $upgradeMethod[] = new Statement($upcall);
                        } else {
                            throw new \Exception('afterColumn is undefined.');
                        }
                        */
                        break;
                    case 'D':
                        $alterTable->dropColumnByName($diff->name);
                        /*
                        $upcall = new MethodCallExpr('$this', 'dropColumnByName', [$tableName, $diff->name]);
                        $upgradeMethod->getBlock()->appendLine(new Statement($upcall));
                        */
                        break;
                    default:
                        $this->logger->warn('** unsupported flag.');
                        continue;
                    }

                    // Genearte query statement
                    $sql = $alterTable->toSql($driver, new ArgumentArray());
                    $upcall = new MethodCallExpr('$this', 'query', [$sql]);
                    $upgradeMethod->getBlock()->appendLine(new Statement($upcall));
                }
            } else {
                $this->logger->info(sprintf("Found schema '%s' to be imported to '%s'", $b, $tableName), 1);
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $upcall = new MethodCallExpr('$this', 'importSchema', [new Raw('new '.get_class($b))]);
                $upgradeMethod->getBlock()->appendLine(new Statement($upcall));

                $downcall = new MethodCallExpr('$this', 'dropTable', [$tableName]);
                $downgradeMethod->getBlock()->appendLine(new Statement($downcall));
            }
        }

        $filename = $this->generateFilename($taskName, $time);
        $path = $this->migrationDir.DIRECTORY_SEPARATOR.$filename;
        if (false === file_put_contents($path, $template->render())) {
            throw new RuntimeException("Can't write migration script to $path.");
        }

        return array($template->class->name, $path);
    }
}
