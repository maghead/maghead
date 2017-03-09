<?php
use Maghead\Types\DateTime as OurDateTime;

class MagheadDateTimeTest extends PHPUnit\Framework\TestCase
{
    public function testToString()
    {
        $dateTime = new OurDateTime;
        $this->assertStringMatchesFormat('%i-%i-%iT%i:%i:%i+%i:00', $dateTime->__toString());
    }
}
