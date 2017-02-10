<?php
use Maghead\Deflator;

/**
 * @group hydrate
 */
class DeflatorTest extends PHPUnit_Framework_TestCase
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

    public function testStr()
    {
        $this->assertEquals('1', Deflator::deflate(1, 'str'));
        $this->assertEquals('1.1', Deflator::deflate(1.1, 'str'));
    }

    public function testBool()
    {
        $this->assertEquals(1, Deflator::deflate(1.1, 'bool'));
        $this->assertEquals(0, Deflator::deflate(0, 'bool'));
        $this->assertEquals(null, Deflator::deflate(null, 'bool'));
        $this->assertEquals(false, Deflator::deflate('', 'bool'));
        $this->assertEquals(false, Deflator::deflate('0', 'bool'));
        $this->assertEquals(true, Deflator::deflate('1', 'bool'));
        $this->assertEquals(true, Deflator::deflate('true', 'bool'));
        $this->assertEquals(false, Deflator::deflate('false', 'bool'));
    }
}
