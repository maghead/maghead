<?php

namespace Maghead\Migration;

use RuntimeException;
use ReflectionClass;
use CLIFramework\Logger;
use Maghead\Schema;
use Maghead\TableParser\TableParser;
use Maghead\Schema\Comparator;
use ClassTemplate\ClassFile;
use CodeGen\Expr\MethodCallExpr;
use CodeGen\Statement\Statement;
use CodeGen\Raw;
use CodeGen\ClassMethod;
use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\ArgumentArray;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\Driver\BaseDriver;
use Doctrine\Common\Inflector\Inflector;

class MigrationGenerator
{
    protected $logger;

    protected $migrationDir;

    protected $filenameFormat = '@date@_@name@.php';

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
        $refl = new ReflectionClass('Maghead\Schema\SchemaGenerator');
        $path = $refl->getFilename();

        return dirname($path).DIRECTORY_SEPARATOR.'Templates';
    }

    public function generateFilename($taskName, $time = null)
    {
        if (is_integer($time)) {
            $date = date('Ymd', $time);
        } elseif (is_string($time)) {
            $date = $time;
        } else {
            $date = date('Ymd');
        }
        // Replace non-word charactors into underline
        $taskName = preg_replace('#\W#i', '_', $taskName);
        // $name = Inflector::tableize($taskName);
        return str_replace([
            '@date@', '@name@',
        ], [$date, $taskName], $this->filenameFormat);
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
        $template->extendClass('Maghead\Migration\Migration');

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

    protected function appendQueryStatement(ClassMethod $method, BaseDriver $driver, ToSqlInterface $query, ArgumentArray $args)
    {
        // build query
        $sql = $query->toSql($driver, $args);
        $call = new MethodCallExpr('$this', 'query', [$sql]);
        $method->getBlock()->appendLine(new Statement($call));
    }

    public function generateWithDiff($taskName, $dataSourceId, array $schemas, $time = null)
    {
        $connectionManager = \Maghead\ConnectionManager::getInstance();
        $connection = $connectionManager->getConnection($dataSourceId);
        $driver = $connectionManager->getQueryDriver($dataSourceId);

        $parser = TableParser::create($connection, $driver);
        $tableSchemas = $schemas;
        $existingTables = $parser->getTables();

        $this->logger->info('Found '.count($schemas).' schemas to compare.');

        $template = $this->createClassTemplate($taskName, $time);
        $upgradeMethod = $template->addMethod('public', 'upgrade', array(), '');
        $downgradeMethod = $template->addMethod('public', 'downgrade', array(), '');

        $comparator = new Comparator($driver);

        // schema from runtime
        foreach ($tableSchemas as $key => $a) {
            $table = is_numeric($key) ? $a->getTable() : $key;

            if (!in_array($table, $existingTables)) {
                $this->logger->info(sprintf("Found schema '%s' to be imported to '%s'", $a, $table), 1);
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $upcall = new MethodCallExpr('$this', 'importSchema', [new Raw('new '.get_class($a))]);
                $upgradeMethod->getBlock()->appendLine(new Statement($upcall));

                $downcall = new MethodCallExpr('$this', 'dropTable', [$table]);
                $downgradeMethod->getBlock()->appendLine(new Statement($downcall));
                continue;
            }

            // revsersed schema 
            $b = $parser->reverseTableSchema($table, $a);

            $diffs = $comparator->compare($b, $a);
            if (empty($diffs)) {
                continue;
            }

            // generate alter table statement.
            foreach ($diffs as $diff) {
                switch ($diff->flag) {
                case 'A':
                    $alterTable = new AlterTableQuery($table);
                    $alterTable->addColumn($diff->getAfterColumn());
                    $this->appendQueryStatement($upgradeMethod, $driver, $alterTable, new ArgumentArray());

                    $alterTable = new AlterTableQuery($table);
                    $alterTable->dropColumn($diff->getAfterColumn());
                    $this->appendQueryStatement($downgradeMethod, $driver, $alterTable, new ArgumentArray());
                    break;
                case 'M':
                    $alterTable = new AlterTableQuery($table);
                    $after = $diff->getAfterColumn();
                    $before = $diff->getBeforeColumn();
                    if (!$after || !$before) {
                        throw new LogicException('afterColumn or beforeColumn is undefined.');
                    }
                    // Check primary key
                    if ($before->primary != $after->primary) {
                        // primary key requires another sub-statement "ADD PRIMARY KEY .."
                        $alterTable->add()->primaryKey([$after->name]);
                    }
                    $alterTable->modifyColumn($after);
                    $this->appendQueryStatement($upgradeMethod, $driver, $alterTable, new ArgumentArray());

                    $alterTable = new AlterTableQuery($table);
                    $alterTable->modifyColumn($before);
                    $this->appendQueryStatement($downgradeMethod, $driver, $alterTable, new ArgumentArray());

                    break;
                case 'D':
                    $alterTable = new AlterTableQuery($table);
                    $alterTable->dropColumnByName($diff->name);
                    $this->appendQueryStatement($upgradeMethod, $driver, $alterTable, new ArgumentArray());

                    $alterTable = new AlterTableQuery($table);
                    $alterTable->addColumn($diff->getBeforeColumn());
                    $this->appendQueryStatement($downgradeMethod, $driver, $alterTable, new ArgumentArray());
                    break;
                default:
                    $this->logger->warn('** unsupported flag.');
                    continue;
                }
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
