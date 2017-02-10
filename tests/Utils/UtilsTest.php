<?php
use Maghead\Utils;

/**
 * @group utils
 */
class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function testEvaluateFunction()
    {
        $this->assertEquals(1, Utils::evaluate(1));
        $this->assertEquals(2, Utils::evaluate(function () { return 2; }));
    }
}
