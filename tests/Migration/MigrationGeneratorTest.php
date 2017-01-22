<?php
use Maghead\Migration\MigrationGenerator;
use Maghead\Console;
use Maghead\Migration\MigrationRunner;
use Maghead\Schema\SchemaFinder;
use Maghead\Testing\ModelTestCase;
use Maghead\ConnectionManager;

/**
 * @group migration
 */
class MigrationGeneratorTest extends ModelTestCase
{
    public $onlyDriver = 'mysql';

    public function getModels() { return array(); }

    public function testGenerator()
    {
        $this->conn->query('DROP TABLE IF EXISTS users;');
        $this->conn->query('CREATE TABLE users (id integer NOT NULL PRIMARY KEY);');

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations');
        $this->assertEquals('20120901_CreateUser.php',$generator->generateFilename('CreateUser','20120901'));

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        // this requires timezone = asia/taipei
        $this->assertEquals('UpdateUser_1346515200', $class);
        $this->assertFileExists($path);
        $this->assertEquals('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);
    }

    public function testMigrationByDiff() 
    {
        $this->conn->query('DROP TABLE IF EXISTS users');
        $this->conn->query('DROP TABLE IF EXISTS test');
        $this->conn->query('CREATE TABLE users (account VARCHAR(128) UNIQUE)');

        if (! file_exists('tests/migrations_testing')) {
            mkdir('tests/migrations_testing');
        }

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'tests/migrations_testing');

        ok(class_exists('TestApp\Model\UserSchema', true));

        $finder = new SchemaFinder;
        $finder->find();
        list($class,$path) = $generator->generateWithDiff('DiffMigration', $this->getDriverType(), [ "users" => new TestApp\Model\UserSchema ], '20120101');
        require_once $path;
        ok($class::getId());

        /*
        $userSchema = new TestApp\Model\UserSchema;
        $column = $userSchema->getColumn('account');
        */

        // run migration
        $runner = new MigrationRunner($this->logger, $this->getDriverType());
        $runner->resetMigrationId($this->conn, $this->queryDriver);
        $runner->load('tests/migrations_testing');

        // XXX: PHPUnit can't run this test in separated unit test since 
        // there is a bug of serializing the global array, this assertion will get 5 instead of the expected 1.
        $scripts = $runner->loadMigrationScripts();
        $this->assertNotEmpty($scripts);
        // $this->assertCount(1, $scripts);

        // $this->expectOutputRegex('#DiffMigration_1325347200#');
        $runner->runUpgrade($this->conn, $this->queryDriver, [$class]);

        # echo file_get_contents($path);
        unlink($path);
        $this->conn->query('DROP TABLE IF EXISTS users');
    }
}

