<?php
use SQLBuilder\Column;

class FooMigration extends LazyRecord\Migration\BaseMigration 
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

class BaseMigrationTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
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
    }
}

