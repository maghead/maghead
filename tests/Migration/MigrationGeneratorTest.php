<?php
use Maghead\Migration\MigrationGenerator;
use Maghead\Migration\MigrationLoader;
use Maghead\Console;
use Maghead\Migration\MigrationRunner;
use Maghead\Schema\SchemaFinder;
use Maghead\Testing\ModelTestCase;

/**
 * @group migration
 */
class MigrationGeneratorTest extends ModelTestCase
{
    protected $onlyDriver = 'mysql';

    const MIGRATION_SCRIPT_DIR = 'tests/migrations';

    public function getModels()
    {
        return [];
    }

    public function setUp()
    {
        parent::setUp();

        if (! file_exists(self::MIGRATION_SCRIPT_DIR)) {
            mkdir(self::MIGRATION_SCRIPT_DIR);
        }
    }

    public function testGenerator()
    {
        $this->conn->query('DROP TABLE IF EXISTS users;');
        $this->conn->query('CREATE TABLE users (id integer NOT NULL PRIMARY KEY);');

        $generator = new MigrationGenerator($this->logger, self::MIGRATION_SCRIPT_DIR);
        $this->assertEquals('20120901_CreateUser.php', $generator->generateFilename('CreateUser', '20120901'));

        list($scriptClass, $path) = $generator->generate('UpdateUser', '20120902');
        // this requires timezone = Asia/Taipei

        $this->assertEquals('UpdateUser_1346515200', $scriptClass);
        $this->assertFileExists($path);
        $this->assertEquals('tests/migrations/20120902_UpdateUser.php', $path);
        $this->assertFileEquals('tests/migrations/20120902_UpdateUser.php.expected', $path);
        unlink($path);
    }

    public function testMigrationByDiff()
    {
        $this->conn->query('DROP TABLE IF EXISTS users');
        $this->conn->query('DROP TABLE IF EXISTS test');
        $this->conn->query('CREATE TABLE users (account VARCHAR(128) UNIQUE)');

        $generator = new MigrationGenerator($this->logger, self::MIGRATION_SCRIPT_DIR);

        ok(class_exists('TestApp\Model\UserSchema', true));

        $finder = new SchemaFinder;
        $finder->find();

        list($scriptClass, $path) = $generator->generateWithDiff('DiffMigration',
            $this->getCurrentDriverType(),
            [ "users" => new TestApp\Model\UserSchema ],
            '20120101');

        $this->assertFileExists('tests/migrations/20120101_DiffMigration.php.expected', $path);

        require_once $path;

        // Convert date string into timestamp
        $this->assertEquals('1325347200', $scriptClass::getId());

        MigrationLoader::findIn(self::MIGRATION_SCRIPT_DIR);

        $scripts = MigrationLoader::getDeclaredMigrationScripts();
        $this->assertNotEmpty($scripts);

        // run migration
        $runner = new MigrationRunner($this->conn, $this->queryDriver, $this->logger, [$scriptClass]);
        $runner->resetMigrationTimestamp();
        $runner->runUpgrade();

        unlink($path);
        $this->conn->query('DROP TABLE IF EXISTS users');
    }
}
