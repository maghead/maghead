<?php
namespace LazyRecord\Migration;
use SQLBuilder\MigrationBuilder;
use LazyRecord\Console;

class BaseMigration
{
    public $driver;
    public $builder;
    public $connection;
    public $logger;

    public function __construct($dsId)
    {
        $connectionManager = ConnectionManager::getInstance();
        $this->driver = $connectionManager->getQueryDriver($dsId);
        $this->connection = $connectionManager->getConnection($dsId);
        $this->builder = new MigrationBuilder($this->driver);
        $this->logger  = Console::getInstance()->getLogger();
    }

    public function executeCommand($m,$a) {
        $sql = call_user_func_array( array($this->builder,$m) , $a );
        $stm = $this->connection->query($sql);
        $this->logger->info('QueryOK: ' . $sql);
    }

    public function upgrade() { }
    public function downgrade() { }

    public function runUpgrade()
    {
        $this->upgrade();
    }

    public function runDowngrade() 
    {
        $this->downgrade();
    }

    public function __call($m,$a) {
        $this->executeCommand($m,$a);
    }
}



