<?php
namespace Lazy\Command;
use CLIFramework\Command;
use Lazy\Schema;
use Exception;

class BuildSqlCommand extends \CLIFramework\Command
{

    public function options($opts)
    {

    }


    public function brief()
    {
        return 'build sql';
    }

    public function execute($configFile = null)
    {
        $options = $this->getOptions();
        $logger  = $this->getLogger();

        $config = new \Lazy\ConfigLoader;

        if( ! $configFile ) 
            $configFile = 'config/lazy.php';

        $logger->info("Checking config $configFile");
        if( file_exists($configFile) ) {
            if( $options->config )
                $config->load( $options->config->value );
            else
                $config->load( $configFile );
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
            $this->getLogger()->info( "Building SQL for $class" );

            $schema = new $class;
            $sql = $builder->build($schema);
            $conn->query( $sql );
            $error = $conn->errorInfo();
            if( $error[1] ) {
                $this->getLogger()->error( var_export( $error , true ) );
            }
        }
    }
}



