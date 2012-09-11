<?php
class PgsqlBookModelTest extends BookModelTest 
{
    public $driver = 'pgsql';
    public function setUp() {
        if( ! extension_loaded('pgsql') ) {
            $this->markTestSkipped('pgsql extension is required for model testing');
        }
        parent::setUp();
    }
}
