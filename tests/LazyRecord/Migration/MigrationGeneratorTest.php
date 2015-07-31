<?php
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Console;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Testing\ModelTestCase;
use LazyRecord\ConnectionManager;

class MigrationGeneratorTest extends ModelTestCase
{
    public function getModels() { return array(); }

    public function testGenerator()
    {
        $connectionManager = ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection($this->getDriverType());
        $pdo->query('DROP TABLE IF EXISTS users;');
        $pdo->query('CREATE TABLE users (id integer NOT NULL PRIMARY KEY);');

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations');
        $this->assertEquals('20120901_CreateUser.php',$generator->generateFilename('CreateUser','20120901'));

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        $this->assertEquals('UpdateUser_1346515200', $class);
        $this->assertFileExists($path);
        $this->assertEquals('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);
    }

    public function testMigrationByDiff() 
    {
        if ($this->getDriverType() == 'sqlite') {
            $this->markTestSkipped('sqlite migration tests skipped');
            return;
        }


        $connectionManager = ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection($this->getDriverType());
        $pdo->query('DROP TABLE IF EXISTS users');
        $pdo->query('DROP TABLE IF EXISTS test');
        $pdo->query('CREATE TABLE users (account VARCHAR(128) UNIQUE)');

        if (! file_exists('tests/migrations_testing')) {
            mkdir('tests/migrations_testing');
        }

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations_testing');

        ok(class_exists('TestApp\Model\UserSchema', true));

        $finder = new SchemaFinder;
        $finder->find();
        list($class,$path) = $generator->generateWithDiff('DiffMigration', $this->getDriverType(), [ new TestApp\Model\UserSchema ], '20120101');
        require_once $path;
        ok($class::getId());

        /*
        $userSchema = new TestApp\Model\UserSchema;
        $column = $userSchema->getColumn('account');
        */

        /*
         */

        // run migration
        $runner = new MigrationRunner($this->getDriverType());
        $runner->resetMigrationId($this->getDriverType());
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
    }
}

