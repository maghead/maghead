<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use Exception;

class BuildSqlCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        // --rebuild
        $opts->add('rebuild','rebuild all SQL schema.');
    }

    public function brief()
    {
        return 'build sql and insert into database.';
    }

    public function execute()
    {
        // support for schema file or schema class names
        $schemas = func_get_args();


        $options = $this->options;
        $logger  = $this->logger;

        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->initForBuild();

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();

        $logger->info("Initialize connection manager...");

        // XXX: from config files
        $id = 'default';
        $conn = $connectionManager->getConnection($id);
        $type = $connectionManager->getDataSourceDriver($id);
        $driver = $connectionManager->getQueryDriver($id);

        $logger->info("Initialize schema builder...");
        $builder = new \LazyRecord\Schema\SqlBuilder($type,$driver); // driver

        $logger->info("Finding schema classes...");


        // find schema classes 
        $finder = new SchemaFinder;
        $args = func_get_args();
        if( count($args) ) {
            $finder->paths = $args;
        } elseif( $paths = $loader->getSchemaPaths() ) {
            $finder->paths = $paths;
        }
        $finder->loadFiles();

        // load class from class map
        if( $classMap = $loader->getClassMap() ) {
            foreach( $classMap as $file => $class ) {
                if( ! is_integer($file) && is_string($file) )
                    require $file;
            }
        }

        $classes = $finder->getSchemaClasses();

        $fp = fopen('schema.sql','a+');

        foreach( $classes as $class ) {
            $logger->info( "Building SQL for $class" );

            fwrite( $fp , "-- schema $class\n" );

            $schema = new $class;
            $sqls = $builder->build($schema);
            foreach( $sqls as $sql ) {

                $logger->info("--- SQL for $class ");
                $logger->info( $sql );
                fwrite( $fp , $sql . "\n" );

                $conn->query( $sql );
                $error = $conn->errorInfo();
                if( $error[1] ) {
                    $msg =  $class . ': ' . var_export( $error , true );
                    $logger->error($msg);
                    fwrite( $fp , $msg);
                }
            }

            $modelClass = $schema->getModelClass();
            $schema->bootstrap( new $modelClass );
        }

        $logger->info('Schema SQL is generated, please check schema.sql file.');
        fclose($fp);
    }
}



