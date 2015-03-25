<?php
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Console;

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{
    function testGenerator()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('create table users (id integer not null primary key);');

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations');
        ok($generator);

        is('20120901_CreateUser.php',$generator->generateFilename('CreateUser','20120901'));

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        is('UpdateUser_1346515200', $class);
        path_ok($path);
        is('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }

    public function testDiffMigration() 
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 
            'driver' => 'mysql',
            'dsn' =>  @$_ENV['DB_MYSQL_DSN'],
            'user' => @$_ENV['DB_MYSQL_USER'],
            'pass' => @$_ENV['DB_MYSQL_PASS'],
        ));

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('drop table if exists users');
        $pdo->query('drop table if exists test');
        $pdo->query('create table users (account varchar(128) unique);');

        if( ! file_exists('tests/migrations_testing') )
            mkdir('tests/migrations_testing');
        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations_testing');
        ok($generator);

        ok( class_exists( 'TestApp\Model\UserSchema', true) );
        $finder = new \LazyRecord\Schema\SchemaFinder;
        $finder->find();
        list($class,$path) = $generator->generateWithDiff('DiffMigration','default',$finder->getSchemas(),'20120101');
        ok($class);
        ok($path);
        require_once $path;
        path_ok($path);
        class_ok($class);

        ok($class::getId());

        // run migration
        $runner = new LazyRecord\Migration\MigrationRunner('default');
        ok($runner);
        $runner->resetMigrationId('default');
        $runner->load('tests/migrations_testing');

        $scripts = $runner->getMigrationScripts();
        ok($scripts);

        $this->expectOutputRegex('#DiffMigration_1325347200#');
        $runner->runUpgrade();

        # echo file_get_contents($path);
        unlink($path);

        $pdo->query('drop table if exists users');
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }
}

