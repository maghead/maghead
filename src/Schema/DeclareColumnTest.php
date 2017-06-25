<?php

namespace Maghead\Schema;

use Maghead\Schema\DeclareSchema;
use PHPUnit\Framework\TestCase;

class DeclareColumnTest extends TestCase
{
    public function testPrimaryKeyColumn()
    {
        $column = new DeclareColumn(new DeclareSchema, 'foo');
        $column->primary()
            ->integer()
            ->unsigned()
            ->autoIncrement()
            ->notNull();
        $this->assertEquals('foo', $column->name);
        $this->assertTrue($column->primary);
        $this->assertEquals('int', $column->type);
        $this->assertTrue($column->notNull);
    }

    public function testPlainArrayValidValues()
    {
        $column = new DeclareColumn(new DeclareSchema, 'role');
        $column->validValues([ 'admin', 'user', 'guest' ]);
        $this->assertEquals([ 'admin', 'user', 'guest' ], $column->getValidValues());
    }

    public function testNumberArrayValidValues()
    {
        $column = new DeclareColumn(new DeclareSchema, 'role');
        $column->validValues([0,1,2,3]);
        $this->assertEquals([0,1,2,3], $column->getValidValues());
    }

    public function testArrayValidValues()
    {
        $column = new DeclareColumn(new DeclareSchema, 'role');
        $column->validValues([
            ['value' => 'admin'],
            ['value' => 'user'],
            ['value' => 'guest'],
        ]);
        $this->assertEquals([
            ['value' => 'admin'],
            ['value' => 'user'],
            ['value' => 'guest'],
        ], $column->getValidValues());

    }

}
