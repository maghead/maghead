<?php
class TestSeed
{
    public static function seed()
    {
        $ret = Name::create(array('name' => 'Add','country' => 'Taiwan','address' => 'Address' ));
    }
}
