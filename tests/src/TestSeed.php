<?php
class TestSeed
{
    public static function seed() 
    {
        $name = new TestApp\Model\Name;
        $ret = $name->create(array('name' => 'Add','country' => 'Taiwan','address' => 'Address' ));
    }
}
