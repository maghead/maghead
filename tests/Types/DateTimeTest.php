<?php
use Maghead\Types\DateTime as OurDateTime;

class MagheadDateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $dateTime = new OurDateTime;
        ok($dateTime->__toString());
    }
}

