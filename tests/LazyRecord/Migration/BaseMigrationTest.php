<?php
use SQLBuilder\Column;

class FooMigration extends LazyRecord\Migration\Migration 
{
    public function upgrade() 
    {
        $this->addColumn('foo', 
            Column::create('address')
                ->type('varchar(128)')
                ->default('(none)')
                ->notNull()
        );
    }
}

class MigrationTest extends PHPUnit_Framework_TestCase
{
    public function testMigrationUpgrade()
    {
        ob_start();
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array(
            'dsn' => 'sqlite::memory:'
        ));

        $conn = $connm->getConnection('default');
        ok($conn);

        $conn->query('CREATE TABLE foo (id integer primary key, name varchar(32));');

        $migration = new FooMigration('default');
        ok($migration);
        $migration->upgrade();

        $connm->removeDataSource('default');
        ob_end_clean();
    }
}

