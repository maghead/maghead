<?php
use SQLBuilder\Column;
use SQLBuilder\Driver\PDODriverFactory;
use Maghead\Migration\Migration;
use Maghead\Testing\ModelTestCase;
use TestApp\Model\NameSchema;

class AddCellphoneMigration extends Migration
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
    protected $onlyDriver = ['mysql', 'pgsql'];

    public function models()
    {
        return [new NameSchema];
    }

    public function testUpgradeWithAddColumnByCallable()
    {
        $migration = new AddCellphoneMigration($this->conn, $this->queryDriver, $this->logger);
        $migration->upgrade();
    }
}
