<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\Utils;
use LazyRecord\Command\BaseCommand;

use CornelTek\DBUtil;
use Exception;

class CreateDBCommand extends BaseCommand
{

    public function brief()
    {
        return 'Create database from config';
    }

    public function createDB($ds)
    {
        if (empty($ds))
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

    public function execute()
    {
        // support for schema file or schema class names
        $options = $this->options;
        $logger  = $this->logger;
        $dataSource = $this->getCurrentDataSourceId();
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $this->createDB( $connectionManager->getDataSource($dataSource));
        $this->logger->info('Done');
    }
}


