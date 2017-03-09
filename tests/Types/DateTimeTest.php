<?php
use Maghead\Types\DateTime as OurDateTime;

class MagheadDateTimeTest extends PHPUnit\Framework\TestCase
{
    public function testToString()
    {
        $dateTime = new OurDateTime;
        ok($dateTime->__toString());
    }
}
