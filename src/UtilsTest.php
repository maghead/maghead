<?php

namespace Maghead;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Column\UUIDPrimaryKeyColumn;

/**
 * @group utils
 */
class UtilsTest extends \PHPUnit\Framework\TestCase
{

    public function resolveClassDataProvider()
    {
        $data = [];

        // load the class by the default namespace roots
        $data[] = [UUIDPrimaryKeyColumn::class, 'UUIDPrimaryKeyColumn', ['Maghead\\Schema\\Column']];

        // test reference object to look up the class
        $data[] = [UUIDPrimaryKeyColumn::class, 'UUIDPrimaryKeyColumn', [], new DeclareSchema, ['Column']];
        return $data;
    }

    /**
     * @dataProvider resolveClassDataProvider
     */
    public function testResolveClass($expect, $name, array $nsRoots, $refObject = null, array $subNsNames = [])
    {
        $class = Utils::resolveClass($name, $nsRoots, $refObject, $subNsNames);
        $this->assertEquals($expect, $class);
    }

    public function testEvaluateFunction()
    {
        $this->assertEquals(1, Utils::evaluate(1));
        $this->assertEquals(2, Utils::evaluate(function () { return 2; }));
    }
}
