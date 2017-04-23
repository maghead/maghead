<?php
use PHPUnit\Framework\TestCase;
use Maghead\Schema\DeclareSchema;

class NoLocalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

    public function schema()
    {
        $this->removeColumn('id');

        $this->column('id2')
            ->integer()
            ->unsigned()
            ;
    }
}

class LocalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

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

class GlobalPrimaryKeySchema extends DeclareSchema
{
    var $enableHiddenPrimaryKey = false;

    public function schema()
    {
        $this->column('uuid', 'uuid');
    }
}


class DeclareSchemaTest extends TestCase
{

    public function testFindGlobalPrimaryKey()
    {
        $schema = new GlobalPrimaryKeySchema;
        $key = $schema->findGlobalPrimaryKey();
        $this->assertNotNull($key);
        $this->assertEquals('uuid', $key);
    }

    public function testFindLocalPrimaryKeyFailed()
    {
        $schema = new NoLocalPrimaryKeySchema;
        $key = $schema->findLocalPrimaryKey();
        $this->assertNull($key);
    }

    public function testFindLocalPrimaryKey()
    {
        $schema = new LocalPrimaryKeySchema;
        $key = $schema->findLocalPrimaryKey();
        $this->assertEquals('id', $key);
    }
}
