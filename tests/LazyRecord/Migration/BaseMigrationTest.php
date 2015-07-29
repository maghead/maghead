<?php
use SQLBuilder\Column;

class FooMigration extends LazyRecord\Migration\Migration 
{
    public function upgrade() 
    {
        $this->addColumnByCallable('foo', function($column) {
            $column->type('varchar(128)')
                ->default('(none)')
                ->notNull();
        });
    }
}

class MigrationTest extends PHPUnit_Framework_TestCase
{
    public function testMigrationUpgrade()
    {
        ob_start();
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->free();
        $connm->addDataSource('sqlite',array(
            'dsn' => 'sqlite::memory:'
        ));

        $conn = $connm->getConnection('sqlite');
        $this->assertNotNull($conn);

        $driver = $connm->getQueryDriver('sqlite');
        $this->assertNotNull($driver);

        $conn->query('CREATE TABLE foo (id integer primary key, name varchar(32));');

        $migration = new FooMigration($driver, $conn);
        ok($migration);
        $migration->upgrade();

        $connm->removeDataSource('sqlite');
        ob_end_clean();
    }
}

