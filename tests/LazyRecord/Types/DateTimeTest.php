<?php
use LazyRecord\Types\DateTime as OurDateTime;

class LazyRecordDateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $dateTime = new OurDateTime;
        ok($dateTime->__toString());
    }
}

