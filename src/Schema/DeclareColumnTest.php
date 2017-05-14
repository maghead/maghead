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
            ->autoIncrement()
            ->notNull();
        $this->assertEquals('foo', $column->name);
        $this->assertTrue($column->primary);
        $this->assertEquals('int', $column->type);
        $this->assertTrue($column->notNull);
    }
}
