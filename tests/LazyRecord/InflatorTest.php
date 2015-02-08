<?php
use LazyRecord\Inflator;

class InflatorTest extends PHPUnit_Framework_TestCase
{


    public function testBooleanFalse()
    {
        $this->assertFalse(Inflator::inflate( 'false', 'bool'));
        $this->assertFalse(Inflator::inflate( 'FALSE', 'bool'));
        $this->assertFalse(Inflator::inflate( '0', 'bool'));
    }

    public function testBooleanTrue()
    {
        $this->assertTrue(Inflator::inflate( 'true', 'bool' ));
        $this->assertTrue(Inflator::inflate( 'TRUE', 'bool' ) );
        $this->assertTrue(Inflator::inflate( '1', 'bool' ) );
    }

    public function testFloat()
    {
        is(1.1 , Inflator::inflate( '1.1', 'float' ));
    }

    public function dateStringProvider()
    {
        return [
            ['2010-01-31'],
            ['2010-01-31T00:00:00+08:00'],
            ['2012-01-19 03:10:41'],
        ];
    }

    /**
     * @dataProvider dateStringProvider
     */
    public function testDateTime($datestr)
    {
        $this->assertInstanceOf('DateTime', Inflator::inflate($datestr, 'DateTime' ) );
        // is( '2012-01-19T03:10:41+08:00', $d->format( DateTime::ATOM  ) ); 
        // XXX:
    }
}

