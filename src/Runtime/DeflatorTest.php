<?php

namespace Maghead\Runtime;

use DateTime;

/**
 * @group hydrate
 */
class DeflatorTest extends \PHPUnit\Framework\TestCase
{
    public function testInt()
    {
        $this->assertEquals(1, Deflator::deflate('1', 'int'));
    }

    public function testDatetime()
    {
        $d = new DateTime;
        $dstr = Deflator::deflate($d, 'DateTime');
        $this->assertEquals($d->format(DateTime::ATOM), $dstr);
        $this->assertEquals(null, Deflator::deflate('', 'DateTime'));
        $this->assertEquals(null, Deflator::deflate(null, 'DateTime'));
    }

    public function testFloat()
    {
        $this->assertEquals(1.1, Deflator::deflate('1.1', 'float'));
    }


    public function stringTestDataProvider()
    {
        return [
            ['1', 1],
            ['1.1', 1.1],
            ['', false],
        ];
    }

    /**
     * @dataProvider stringTestDataProvider
     */
    public function testStr($exp, $input)
    {
        $this->assertEquals($exp, Deflator::deflate($input, 'str'));
    }

    public function boolTestDataProvider()
    {
        return [
            [1, 1],
            [0, 0],
            [null, null],
            [false, ''],
            [false, '0'],
            [true, '1'],
            [true, 'true'],
            [false, 'false'],
        ];
    }

    /**
     * @dataProvider boolTestDataProvider
     */
    public function testDeflateBool($exp, $input)
    {
        $this->assertEquals($exp, Deflator::deflate($input, 'bool'));
    }
}
