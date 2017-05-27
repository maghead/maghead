<?php
use Magsql\Column;
use Magsql\Driver\PDODriverFactory;
use Maghead\Migration\Migration;
use Maghead\Testing\ModelTestCase;
use TestApp\Model\NameSchema;

/**
 * Migration Script Class that starts with "Test" won't be included from
 * getDeclaredMigrationScripts
 */
class TestAddCellphoneMigration extends Migration
{
    public function upgrade()
    {
        $this->addColumn('names', function ($column) {
            $column->name('cellphone')
                ->type('varchar(128)')
                ->default('(none)')
                ->notNull();
        });
    }

    public function downgrade()
    {
        $this->dropColumn('names', 'cellphone');
    }
}


/**
 * @group migration
 */
class MigrationTest extends ModelTestCase
{
    protected $onlyDriver = ['mysql'];

    public function models()
    {
        return [new NameSchema];
    }

    public function testUpgradeWithAddColumnByCallable()
    {
        $migration = new TestAddCellphoneMigration($this->conn, $this->queryDriver, $this->logger);
        $migration->upgrade();
        $migration->downgrade();
    }
}
