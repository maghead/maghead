<?php

namespace Maghead;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Column\UUIDPrimaryKeyColumn;

/**
 * @group utils
 */
class UtilsTest extends \PHPUnit\Framework\TestCase
{


    public function searchValueDataProvider()
    {
        return [
            [ false, [1,2,3], 10 ],
            [ true, [1,2,3], 3 ],
            [ true, ["admin", "user", "guest"],  "guest"],
            [ false, ["admin", "user", "guest"],  "foo"],

            [ true, [
                [ "value" => "admin" ],
                [ "value" => "user" ],
                [ "value" => "guest" ],
            ],  "guest"],

            [ true, [
                [ "label" => "Asia", "items" => [  ["value" => "Tokyo"], ["value" => "Taipei"]  ] ],
                [ "label" => "Europe", "items" => [  ["value" => "Paris"], ["value" => "Berlin"]  ] ],
            ],  "Berlin"],
        ];
    }


    /**
     * @dataProvider searchValueDataProvider
     */
    public function testSearchValueWithIndexedArray($found, $array, $needle)
    {
        $this->assertSame($found, Utils::searchValue($array, $needle));
    }

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

    public function testFilterClasses()
    {
        $args = ['examples/books', 'PageApp\\Model\\PageSchema'];
        $classes = Utils::filterClassesFromArgs($args);
        $this->assertEquals(['PageApp\\Model\\PageSchema'], $classes);
    }

    public function testFilterPaths()
    {
        $args = ['examples/books', 'PageApp\\Model\\PageSchema'];
        $classes = Utils::filterPathsFromArgs($args);
        $this->assertEquals(['examples/books'], $classes);
    }
}
