<?php

namespace Maghead\Types;

use PHPUnit\Framework\TestCase;

class MagheadDateTimeTest extends TestCase
{
    public function testToString()
    {
        $d = new DateTime;
        $this->assertStringMatchesFormat('%i-%i-%iT%i:%i:%i+%i:00', $d->__toString());
    }
}
