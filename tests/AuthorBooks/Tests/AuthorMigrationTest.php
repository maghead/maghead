<?php
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\AuthorCollection;
use LazyRecord\Migration\Migration;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;

use LazyRecord\Migration\AutomaticMigration;
use GetOptionKit\OptionResult;
use GetOptionKit\OptionCollection;

class AuthorMigrationTest extends ModelTestCase
{
    public $onlyDriver = 'mysql';

    public function getModels()
    {
        return [];
    }

    public function testAddColumnMigrate()
    {
        $schema = new AuthorSchema;
        $schema->removeColumn('email');
        $this->buildSchemaTables([$schema], true);
        AutomaticMigration::options($options = new OptionCollection);
        $migrate = new AutomaticMigration($this->queryDriver,
            $this->conn,
            OptionResult::create($options, [

            ]));
        $migrate->upgrade();
    }

    public function testRemoveColumnMigrate()
    {
        $schema = new AuthorSchema;
        $schema->column('cellphone')
            ->varchar(30);
        $this->buildSchemaTables([$schema], true);
        AutomaticMigration::options($options = new OptionCollection);
        $migrate = new AutomaticMigration($this->queryDriver,
            $this->conn,
            OptionResult::create($options, [ ]));
        $migrate->upgrade();
    }
}
