<?php
use Maghead\Utils\ArrayUtils;

/**
 * @group utils
 */
class ArrayUtilsTest extends PHPUnit\Framework\TestCase
{
    public function testAssocArrayCheck()
    {
        $a = array(
            'a' => 'b',
            '0' => '1',
        );
        $this->assertTrue(ArrayUtils::is_assoc_array($a));

        $a = array(
            'foo' => 'b',
            'bar' => '1',
        );
        $this->assertTrue(ArrayUtils::is_assoc_array($a));
    }

    public function testIndexedArrayCheck()
    {
        $a = array(
            0 => 'foo',
            1 => 'bar',
        );
        $this->assertTrue(ArrayUtils::is_indexed_array($a));

        $a = array(
            'a' => 'foo',
            1 => 'bar',
        );
        $this->assertFalse(ArrayUtils::is_indexed_array($a));


        $a = array(
            'a' => 'foo',
            1 => 'bar',
        );
        $this->assertFalse(ArrayUtils::is_indexed_array($a));
    }
}
