<?php
namespace LazyRecord\Migration;
use SQLBuilder\MigrationBuilder;
use LazyRecord\ConnectionManager;
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




    /**
     * Execute sql for migration
     *
     * @param string $sql
     */
    public function executeSql($sql) 
    {
        $stm = $this->connection->query($sql);
        $this->logger->info('QueryOK: ' . $sql);
    }


    /**
     * Execute migration sql builder commands
     *
     * @param string $m method name
     * @param array $a method arguments
     */
    public function executeCommand($m,$a) 
    {
        $this->logger->info($m);
        $sql = call_user_func_array( array($this->builder,$m) , $a );
        $this->executeSql($sql);
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



