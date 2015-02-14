<?php
class MysqlModelTest extends BasicCRUDTest
{
    public $driver = 'mysql';
    public $schemaPath = 'tests';

    public function setUp() {
        if (! extension_loaded('mysql')) {
            $this->markTestSkipped('mysql extension is required for testing');
        }
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo_mysql extension is required for testing');
        }
        return parent::setUp();
    }
}

