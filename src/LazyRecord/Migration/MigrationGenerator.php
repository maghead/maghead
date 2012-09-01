<?php
namespace LazyRecord\Migration;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\CodeGen\ClassTemplate;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser;
use LazyRecord\Inflector;

class MigrationGenerator
{
    public $driver;
    public $connection;
    public $migrationDir;

    function __construct($dataSourceId,$migrationDir)
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $this->connection  = $connectionManager->getConnection($dataSourceId);
        $this->driver      = $connectionManager->getQueryDriver($dataSourceId);
        $this->migrationDir = $migrationDir;
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
        $inflector = Inflector::getInstance();
        $name = $inflector->underscore($taskName);
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
        $template = new ClassTemplate($className,array(
            'template' => 'Class.php.twig',
            'template_dirs' => array('src/LazyRecord/Schema/Templates'),
        ));
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
        file_put_contents($path , $template->render() );
        return array( $template->class->name,$path );
    }

    public function generateWithDiff($taskName,$schemas)
    {
        $parser = TableParser::create( $this->driver, $this->connection );
        $tableSchemas = array();

        // database schemas
        $tables = $parser->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $parser->getTableSchema( $table );
        }

        $comparator = new \LazyRecord\Schema\Comparator;

        // schema from runtime
        foreach( $schemas as $b ) {
            $t = $b->getTable();
            $foundTable = isset( $tableSchemas[ $t ] );
            if( $foundTable ) {
                $a = $tableSchemas[ $t ];
                $diff = $comparator->compare( $a , $b );
                // generate alter table statement.

                print_r($diff);
            } else {
                // generate create table statement.
                // use sqlbuilder to build schema sql
            }
        }
    }
}


