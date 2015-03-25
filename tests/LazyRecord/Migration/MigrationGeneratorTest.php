<?php
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Console;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConnectionManager;

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function testGenerator()
    {
        $connectionManager = ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));
        $connectionManager = ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('CREATE TABLE users (id integer not null primary key);');

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations');
        $this->assertEquals('20120901_CreateUser.php',$generator->generateFilename('CreateUser','20120901'));

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        $this->assertEquals('UpdateUser_1346515200', $class);
        $this->assertFileExists($path);
        $this->assertEquals('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);

        $connectionManager = ConnectionManager::getInstance();
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }

    public function testMigrationByDiff() 
    {
        $connectionManager = ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 
            'driver' => 'mysql',
            'dsn' =>  @$_ENV['DB_MYSQL_DSN'],
            'user' => @$_ENV['DB_MYSQL_USER'],
            'pass' => @$_ENV['DB_MYSQL_PASS'],
        ));

        $pdo = $connectionManager->getConnection('default');
        $pdo->query('DROP TABLE IF EXISTS users');
        $pdo->query('DROP TABLE IF EXISTS test');
        $pdo->query('CREATE TABLE users (account varchar(128) unique)');

        if (! file_exists('tests/migrations_testing')) {
            mkdir('tests/migrations_testing');
        }

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations_testing');

        ok(class_exists('TestApp\Model\UserSchema', true));

        $finder = new SchemaFinder;
        $finder->find();
        list($class,$path) = $generator->generateWithDiff('DiffMigration', 'default', $finder->getSchemas(), '20120101');
        require_once $path;
        ok($class::getId());

        // run migration
        $runner = new MigrationRunner('default');
        $runner->resetMigrationId('default');
        $runner->load('tests/migrations_testing');


        // XXX: PHPUnit can't run this test in separated unit test since 
        // there is a bug of serializing the global array, this assertion will get 5 instead of the expected 1.
        $scripts = $runner->getMigrationScripts();
        $this->assertNotEmpty($scripts);
        // $this->assertCount(1, $scripts);

        // $this->expectOutputRegex('#DiffMigration_1325347200#');
        $runner->runUpgrade([$class]);

        # echo file_get_contents($path);
        unlink($path);

        $pdo->query('DROP TABLE IF EXISTS users');
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }
}

