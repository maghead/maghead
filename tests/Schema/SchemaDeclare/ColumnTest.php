<?php
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\DeclareColumn;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $column = new DeclareColumn(new DeclareSchema, 'foo');
        $column->primary()
            ->integer()
            ->autoIncrement()
            ->notNull();
        $this->assertEquals('foo',$column->name);
        $this->assertTrue($column->primary);
        $this->assertEquals('int',$column->type);
        $this->assertTrue($column->notNull);
    }
}

