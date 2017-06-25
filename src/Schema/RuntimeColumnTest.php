<?php

namespace Maghead\Schema;

use PHPUnit\Framework\TestCase;

class RuntimeColumnTest extends TestCase
{
    public function testValidate()
    {
        $column = new RuntimeColumn("foo", [
            "isa" => "str",
            "type" => "varchar",
            "primary" => false,
            "unsigned" => false,
            "notNull" => true,
            "validValues" => [
                'admin',
                'user',
                'guest',
            ],
        ]);

        $ret = $column->validate("foo", [ ]);
        $this->assertFalse($ret->valid);

        $ret = $column->validate("admin", [ ]);
        $this->assertNull($ret);
    }
}
