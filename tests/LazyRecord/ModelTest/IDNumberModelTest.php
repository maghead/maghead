<?php
use LazyRecord\Testing\ModelTestCase;

class IDNumberModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array('TestApp\Model\\IDNumberSchema');
    }

    /**
     * @basedata false
     */
    public function testValidation() 
    {
        $record = new TestApp\Model\IDNumber;
        $ret = $record->create(array( 'id_number' => 'A186679004' ));
        $this->assertResultSuccess($ret);

        $ret = $record->create(array( 'id_number' => 'A222222222' ));
        $this->assertResultFail($ret);

        $ret = $record->create(array( 'id_number' => 'A222' ));
        $this->assertResultFail($ret);
    }
}

