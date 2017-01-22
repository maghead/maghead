<?php
use SQLBuilder\Column;
use SQLBuilder\Driver\PDODriverFactory;
use Maghead\ConnectionManager;
use Maghead\Migration\Migration;

class AddCellphoneMigration extends Migration
{
    public function upgrade()
    {
        $this->addColumn('foo', function($column) {
            $column->name('cellphone')
                ->type('varchar(128)')
                ->default('(none)')
                ->notNull();
        });
    }

    public function downgrade()
    {
        $this->dropColumn('foo', 'cellphone');
    }
}

use Maghead\Testing\ModelTestCase;

/**
 * @group migration
 */
class MigrationTest extends ModelTestCase
{
    public $onlyDriver = 'mysql';

    public function getModels()
    {
        return [];
    }

    public function testUpgradeWithAddColumnByCallable()
    {
        ob_start();
        $this->conn->query('DROP TABLE IF EXISTS foo');
        $this->conn->query('CREATE TABLE foo (id INTEGER PRIMARY KEY, name varchar(32));');
        $migration = new AddCellphoneMigration($this->conn, $this->queryDriver, $this->logger);
        $migration->upgrade();
        $migration->downgrade();
        $this->conn->query('DROP TABLE IF EXISTS foo');
        ob_end_clean();
    }
}

