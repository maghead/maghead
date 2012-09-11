<?php
class MysqlBookModelTest extends BookModelTest {

    public $driver = 'mysql';

    public function setUp() {
        if( ! extension_loaded('mysql') ) {
            $this->markTestSkipped('mysql extension is required for model testing');
        }
        parent::setUp();
    }

}
