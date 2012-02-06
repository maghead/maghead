<?php
namespace Lazy\Command;
use CLIFramework\Command;

class BuildSqlCommand extends \CLIFramework\Command
{
    public function execute()
    {
        $configFile = 'config/lazy.php';
        $loader = new \Lazy\ConfigLoader;
        if( file_exists($configFile) ) {
            if( $options->config )
                $loader->load( $options->config->value );
            else
                $loader->load( $defaultConfigFile );
        }

        $connectionManager = \Lazy\ConnectionManager::getInstance();

        $id = 'default';
        $conn = $connectionManager->getConnection($id);
        $type = $connectionManager->getDataSourceDriver($id);
		$builder = new SchemaSqlBuilder($type); // driver




    }
}



