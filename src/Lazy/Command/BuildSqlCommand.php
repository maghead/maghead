<?php
namespace Lazy\Command;
use CLIFramework\Command;
use Lazy\Schema;

class BuildSqlCommand extends \CLIFramework\Command
{
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

        var_dump( $classes ); 


    }
}



