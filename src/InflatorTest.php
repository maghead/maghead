<?php

namespace Maghead;

use PHPUnit\Framework\TestCase;
/**
 * @group hydrate
 */
class InflatorTest extends TestCase
{
    public function booleanDataProvider()
    {
        return [
            [true, '1'],
            [false, '0'],
            [false, '0'],
        ];
    }

    /**
     * @dataProvider booleanDataProvider
     */
    public function testBooleanTrue($exp, $input)
    {
        $this->assertEquals($exp, Inflator::inflate($input, 'bool'));
    }

    public function testFloat()
    {
        $this->assertEquals(1.1, Inflator::inflate('1.1', 'float'));
    }

    public function testJson()
    {
        $this->assertEquals((object) [ 'foo' => 1 ], Inflator::inflate(json_encode([ 'foo' => 1 ]), 'json'));
    }

    public function dateStringProvider()
    {
        return [
            ['2010-01-31'],
            ['2010-01-31T00:00:00+08:00'],
            ['2012-01-19 03:10:41'],
            ['2012-01-19T03:10:41+08:00'],
        ];
    }

    /**
     * @dataProvider dateStringProvider
     */
    public function testDateTime($datestr)
    {
        $this->assertInstanceOf('DateTime', Inflator::inflate($datestr, 'DateTime'));
    }
}
