<?php
class IDNumberModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array('TestApp\Model\\IDNumberSchema');
    }

    public function testValidation() 
    {
        $record = new TestApp\Model\IDNumber;
        $ret = $record->create(array( 'id_number' => 'A186679004' ));
        ok($ret->success, $ret->message);

        $ret = $record->create(array( 'id_number' => 'A222222222' ));
        not_ok($ret->success, $ret->message);

        $ret = $record->create(array( 'id_number' => 'A222' ));
        not_ok($ret->success, $ret->message);
    }
}

