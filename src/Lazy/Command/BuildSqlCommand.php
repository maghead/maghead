<?php
namespace Lazy\Command;
use CLIFramework\Command;
use Lazy\Schema;

class BuildSqlCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'build sql';
    }

    public function execute()
    {
        $options = $this->getOptions();

        $config = new \Lazy\ConfigLoader;

        $configFile = 'config/lazy.php';
        if( file_exists($configFile) ) {
            if( $options->config )
                $config->load( $options->config->value );
            else
                $config->load( $configFile );
        }

        $connectionManager = \Lazy\ConnectionManager::getInstance();

        $id = 'default';
        $conn = $connectionManager->getConnection($id);
        $type = $connectionManager->getDataSourceDriver($id);
		$builder = new \Lazy\SchemaSqlBuilder($type); // driver

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
                $this->getLogger()->info( var_export( $error , true ) );
            }
        }
    }
}



