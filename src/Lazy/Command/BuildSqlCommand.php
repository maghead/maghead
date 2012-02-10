<?php
namespace Lazy\Command;
use CLIFramework\Command;
use Lazy\Schema;
use Exception;

class BuildSqlCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        $opts->add('c|config:','config file');
    }


    public function brief()
    {
        return 'build sql';
    }

    public function execute($configFile = null)
    {
        $options = $this->getOptions();
        $logger  = $this->getLogger();

        $configFile = 'config/lazy.php';
        $loader = new \Lazy\ConfigLoader;

        if( $options->config )
            $configFile = $options->config->value;

        if( file_exists($configFile) ) {
            if( $options->config )
                $loader->loadConfig( $options->config->value );
            else
                $loader->loadConfig( $configFile );

            $logger->info("config $configFile loaded.");
        }
        else {
            throw new Exception("Config file $configFile not found.");
        }

        $loader->loadDataSources();
        $loader->loadBootstrap();

        $connectionManager = \Lazy\ConnectionManager::getInstance();

        $logger->info("Initialize connection manager...");

        $id = 'default';
        $conn = $connectionManager->getConnection($id);
        $type = $connectionManager->getDataSourceDriver($id);

        $logger->info("Initialize schema builder...");
        $builder = new \Lazy\SchemaSqlBuilder($type); // driver

        $logger->info("Finding schema classes...");

        // find schema classes 
        $finder = new Schema\SchemaFinder;
        $finder->paths = $loader->getSchemaPaths();
        $finder->load();
        $classes = $finder->getSchemas();

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
        }

        $logger->info('Schema SQL is generated, please check schema.sql file.');
        fclose($fp);
    }
}



