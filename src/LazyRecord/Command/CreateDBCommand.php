<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\Utils;

use CornelTek\DBUtil;
use Exception;

class CreateDBCommand extends Command
{

    public function brief()
    {
        return 'create database from config';
    }

    public function createDB($ds)
    {
        if( empty($ds) ) 
            return;

        $dbutil = new DBUtil;
        $user = @$ds['user'];
        $pass = @$ds['pass'];

        if( isset($ds['dsn']) ) {
            $params = Utils::breakDSN($ds['dsn']);
            $ds = array_merge($ds, $params);
        }

        if( isset($ds[':memory:']) ) {
            $this->logger->info('skip :memory: database');
            return;
        }

        if( ! isset($ds['database']) ) {
            $this->logger->notice('database is not defined.');
            return;
        }

        $this->logger->info("creating database {$ds['database']}...");
        $dbutil->create($ds['driver'],array( 
            'username' => $user,
            'password' => $pass,
            'database' => $ds['database'],
        ));
    }

    public function execute($dataSource = null)
    {
        // support for schema file or schema class names
        $options = $this->options;
        $logger  = $this->logger;

        $loader = ConfigLoader::getInstance();
        $loader->loadFromSymbol();
        $loader->initForBuild();

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $dsIds = $connectionManager->getDataSourceIdList();
        if( $dataSource ) {
            $this->createDB( $connectionManager->getDataSource($dataSource));
        } else {
            foreach( $dsIds as $id ) {
                $this->createDB( $connectionManager->getDataSource($id));
            }
        }

        $this->logger->info('Done');
    }
}





