<?php
namespace LazyRecord\Migration;
use Exception;
use RuntimeException;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use CLIFramework\Logger;
use LazyRecord\Schema;
use ClassTemplate\TemplateClassDeclare;
use ClassTemplate\ClassDeclare;
use ClassTemplate\MethodCallExpr;
use ClassTemplate\NewObjectExpr;
use ClassTemplate\Statement;
use ClassTemplate\Raw;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\Comparator;
use LazyRecord\Console;
use Doctrine\Common\Inflector\Inflector;

class MigrationGenerator
{
    public $logger;

    public $migrationDir;

    public function __construct(Logger $logger, $migrationDir)
    {
        $this->migrationDir = $migrationDir;
        if( ! file_exists($this->migrationDir) ) {
            mkdir($this->migrationDir,0755,true);
        }
        $this->logger = $logger; 
    }

    /**
     * Returns code template directory
     */
    protected function getTemplateDirs()
    {
        $refl = new ReflectionClass('LazyRecord\Schema\SchemaGenerator');
        $path = $refl->getFilename();
        return dirname($path) . DIRECTORY_SEPARATOR . 'Templates';
    }

    public function generateFilename($taskName, $time = null)
    {
        $date = date('Ymd');
        if(is_integer($time)) {
            $date = date('Ymd',$time);
        }
        elseif( is_string($time) ) {
            $date = $time;
        }
        $name = Inflector::tableize($taskName);
        return sprintf('%s_%s.php', $date, $taskName);
    }

    public function createClassTemplate($taskName,$time = null) 
    {
        if(!$time) {
            $time = time();
        } elseif( is_string($time) ) {
            $time = strtotime($time);
        }
        $className = $taskName . '_' . $time;
        // $filename
        $template = new ClassDeclare($className);
        $template->extendClass('LazyRecord\Migration\Migration');
        return $template;
    }

    public function generate($taskName,$time = null)
    {
        $template = $this->createClassTemplate($taskName,$time);
        $template->addMethod('public','upgrade',array(),'');
        $template->addMethod('public','downgrade',array(),'');
        $filename = $this->generateFilename($taskName,$time);
        $path = $this->migrationDir . DIRECTORY_SEPARATOR . $filename;
        if ( false === file_put_contents($path, $template->render())) {
            throw new RuntimeException("Can't write template to $path");
        }
        return array($template->class->name, $path);
    }

    public function generateWithDiff($taskName, $dataSourceId, $schemas, $time = null)
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connection  = $connectionManager->getConnection($dataSourceId);
        $driver      = $connectionManager->getQueryDriver($dataSourceId);

        $parser = TableParser::create( $driver, $connection );
        $tableSchemas = $parser->getTableSchemas();

        $this->logger->info( 'Found ' . count($schemas) . ' schemas to compare.' );

        $template = $this->createClassTemplate($taskName,$time);
        $template->useClass('SQLBuilder\Universal\Syntax\Column');
        $upgradeMethod = $template->addMethod('public', 'upgrade', array(),'');
        $downgradeMethod = $template->addMethod('public', 'downgrade', array(),'');

        $comparator = new Comparator;
        // schema from runtime
        foreach( $schemas as $b ) {
            $tableName = $b->getTable();
            $foundTable = isset( $tableSchemas[ $tableName ]);
            if ($foundTable) {
                $a = $tableSchemas[ $tableName ]; // schema object, extracted from database.
                $diffs = $comparator->compare( $a , $b );

                // generate alter table statement.
                foreach ($diffs as $diff) {
                    if ($diff->flag == '+') {
                        $this->logger->info(sprintf("'%s': add column %s", $tableName, $diff->name) , 1);

                        $upcall = new MethodCallExpr('$this','addColumn', [$tableName]);
                        $downcall = new MethodCallExpr('$this','dropColumn', [$tableName, $diff->name]);

                        // filter out useless columns
                        $arg = array();
                        $columnAttributes = $diff->getAfterOrBeforeColumn()->toArray();
                        $column = $diff->getAfterColumn();
                        foreach ($column->toArray() as $key => $value) {
                            if (is_object($value) || is_array($value)) {
                                continue;
                            }
                            if (in_array($key,array('type','unique','default','notNull','null','autoIncrement'))) {
                                $arg[ $key ] = $value;
                            }
                        }
                        $upcall->addArgument(new NewObjectExpr('Column', [$column->name, $column->type, $column->toArray() ]));

                        $upgradeMethod->getBlock()->appendLine(new Statement($upcall));
                        $downgradeMethod->getBlock()->appendLine(new Statement($downcall));

                    } else if ($diff->flag == '-') {
                        $upcall = new MethodCallExpr('$this', 'dropColumn', [$tableName, $diff->name]);
                        $upgradeMethod->getBlock()->appendLine(new Statement($upcall));
                    } else if ($diff->flag == '=') {
                        $this->logger->warn("** column flag = is not supported yet.");
                        continue;
                    } else {
                        $this->logger->warn("** unsupported flag.");
                        continue;
                    }
                }
            } 
            else {
                $this->logger->info(sprintf("Found schema '%s' to be imported to '%s'",$b, $tableName),1);
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $upcall = new MethodCallExpr('$this','importSchema', [new Raw('new ' . get_class($b))]);
                $upgradeMethod->getBlock()->appendLine(new Statement($upcall));

                $downcall = new MethodCallExpr('$this','dropTable', [$tableName]);
                $downgradeMethod->getBlock()->appendLine(new Statement($downcall));
            }
        }

        $filename = $this->generateFilename($taskName,$time);
        $path = $this->migrationDir . DIRECTORY_SEPARATOR . $filename;
        if ( false === file_put_contents($path , $template->render()) ) {
            throw new RuntimeException("Can't write migration script to $path.");
        }
        return array($template->class->name,$path);
    }
}


