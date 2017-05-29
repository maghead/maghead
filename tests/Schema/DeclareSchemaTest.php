<?php
use PHPUnit\Framework\TestCase;
use Maghead\Schema\DeclareSchema;

class TestNoLocalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

    var $virtual = true;

    public function schema()
    {
        $this->removeColumn('id');

        $this->column('id2')
            ->integer()
            ->unsigned()
            ;
    }
}

class TestLocalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

    var $virtual = true;

    public function schema()
    {
        $this->column('id')
            ->integer()
            ->unsigned()
            ->primary()
            ->autoIncrement()
            ;
    }
}

class TestGlobalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

    var $virtual = true;

    public function schema()
    {
        $this->column('uuid', 'uuid-pk');
    }
}


class DeclareSchemaTest extends TestCase
{
    public function testFindGlobalPrimaryKey()
    {
        $schema = new TestGlobalPrimaryKeySchema;
        $key = $schema->findGlobalPrimaryKey();
        $this->assertNotNull($key);
        $this->assertEquals('uuid', $key);
    }

    public function testFindLocalPrimaryKeyFailed()
    {
        $schema = new TestNoLocalPrimaryKeySchema;
        $key = $schema->findLocalPrimaryKey();
        $this->assertNull($key);
    }

    public function testFindLocalPrimaryKey()
    {
        $schema = new TestLocalPrimaryKeySchema;
        $key = $schema->findLocalPrimaryKey();
        $this->assertEquals('id', $key);
    }
}
