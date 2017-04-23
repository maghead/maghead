<?php
use PHPUnit\Framework\TestCase;
use Maghead\Schema\DeclareSchema;

class NoLocalPrimaryKeySchema extends DeclareSchema
{
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


class DeclareSchemaTest extends TestCase
{
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
