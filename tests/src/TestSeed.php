<?php

class TestSeed
{
    public static function seed() 
    {
        $name = new TestApp\Name;
        $ret = $name->create(array('name' => 'Add','country' => 'Taiwan','address' => 'Address' ));
    }
}



