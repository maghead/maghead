<?php
namespace LazyRecord\Command\DbCommand;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConfigLoader;
use LazyRecord\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use PDO;

class RecreateCommand extends CreateCommand
{
    public function brief() 
    {
        return 're-create database bases on the current config.';
    }

    public function execute()
    {
        $dropCommand = $this->createCommand('LazyRecord\Command\DbCommand\DropCommand');
        $dropCommand->options = $this->options;
        $dropCommand->execute();
        parent::execute();
    }

}



