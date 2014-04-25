<?php
namespace LazyRecord\Migration;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CLIFramework\Command;
use LazyRecord\Schema;
use ClassTemplate\ClassTemplate;
use ClassTemplate\MethodCall;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Inflector;
use LazyRecord\Schema\Comparator;
use LazyRecord\Console;


class MigrationGenerator
{
    public $logger;

    public $migrationDir;

    function __construct($migrationDir)
    {
        $this->migrationDir = $migrationDir;
        if( ! file_exists($this->migrationDir) ) {
            mkdir($this->migrationDir,0755,true);
        }
        $this->logger = Console::getInstance()->getLogger();
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
            'template' => 'MigrationClass.php.twig',
            'template_dirs' => $this->getTemplateDirs(),
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

    public function generateWithDiff($taskName,$dataSourceId,$schemas,$time = null)
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connection  = $connectionManager->getConnection($dataSourceId);
        $driver      = $connectionManager->getQueryDriver($dataSourceId);

        $parser = TableParser::create( $driver, $connection );
        $tableSchemas = $parser->getTableSchemas();

        $this->logger->info( 'Found ' . count($schemas) . ' schemas to compare.' );

        $template = $this->createClassTemplate($taskName,$time);
        $upgradeMethod = $template->addMethod('public','upgrade',array(),'');
        $downgradeMethod = $template->addMethod('public','downgrade',array(),'');

        $comparator = new Comparator;
        // schema from runtime
        foreach( $schemas as $b ) {
            $t = $b->getTable();
            $foundTable = isset( $tableSchemas[ $t ] );
            if( $foundTable ) {
                $a = $tableSchemas[ $t ]; // schema object, extracted from database.
                $diffs = $comparator->compare( $a , $b );

                // generate alter table statement.
                foreach( $diffs as $diff ) {
                    $call = new MethodCall;
                    $dcall = new MethodCall; // downgrade method call

                    if( $diff->flag == '+' ) {
                        $this->logger->info(sprintf("'%s': add column %s",$t,$diff->name) , 1);

                        $call->method('addColumn');
                        $call->addArgument('"'. $t . '"'); // table

                        $dcall->method('dropColumn');
                        $dcall->addArgument('"'. $t . '"'); // table
                        $dcall->addArgument('"'. $diff->name . '"'); // table

                        // filter out useless columns
                        $data = array();
                        foreach( $diff->column->toArray() as $key => $value ) {
                            if( is_object($value) )
                                continue;
                            if( is_array($value) )
                                continue;

                            if( in_array($key,array(
                                'type','primary','name','unique','default','notNull','null','autoIncrement',)
                            )) {
                                $data[ $key ] = $value;
                            }
                        }
                        $call->addArgument($data);
                    }
                    elseif( $diff->flag == '-' ) {
                        $call->method('dropColumn');
                        $call->addArgument('"'. $t . '"'); // table
                        $call->addArgument('"'. $diff->name . '"');
                    }
                    elseif( $diff->flag == '=' ) {
                        $this->logger->warn("** column flag = is not supported yet.");
                    }
                    else {
                        $this->logger->warn("** unsupported flag.");
                    }
                    $upgradeMethod->code .= $call->render() . "\n";
                    $downgradeMethod->code .= $dcall->render() . "\n";
                }
            } 
            else {
                $this->logger->info(sprintf("Found schema '%s' to be imported to '%s'",$b,$t),1);
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $call = new MethodCall;
                $call->method('importSchema');
                $call->addArgument( 'new ' . $b ); // for dynamic schema declare, it casts to the model class name.

                $dcall = new MethodCall;
                $dcall->method('dropTable');
                $dcall->addArgument("'$t'");
                $upgradeMethod->code .= $call->render() . "\n";
                $downgradeMethod->code .= $dcall->render() . "\n";
            }
        }

        $filename = $this->generateFilename($taskName,$time);
        $path = $this->migrationDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path , $template->render());
        return array( $template->class->name,$path );
    }
}


