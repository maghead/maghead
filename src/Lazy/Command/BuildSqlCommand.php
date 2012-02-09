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
        $config = new \Lazy\ConfigLoader;

        if( $options->config )
            $configFile = $options->config->value;

        if( file_exists($configFile) ) {
            if( $options->config )
                $config->load( $options->config->value );
            else
                $config->load( $configFile );

            $logger->info("config $configFile loaded.");
        }
        else {
            throw new Exception("Config file $configFile not found.");
        }

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
        $finder->paths = $config->getSchemaPaths();
        $finder->load();
		$classes = $finder->getSchemas();

        foreach( $classes as $class ) {
            $logger->info( "Building SQL for $class" );

            $schema = new $class;
            $sqls = $builder->build($schema);
            foreach( $sqls as $sql ) {
                $conn->query( $sql );
                $error = $conn->errorInfo();
                if( $error[1] ) {
                    $logger->error( $class . ': ' . var_export( $error , true ) );
                }
            }
        }
    }
}



