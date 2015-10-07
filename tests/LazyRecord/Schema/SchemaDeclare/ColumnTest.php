<?php
class ColumnTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $column = new LazyRecord\Schema\DeclareColumn('foo');
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

