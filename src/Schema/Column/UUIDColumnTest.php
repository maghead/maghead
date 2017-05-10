<?php

namespace Maghead\Schema\Column;

use Maghead\Testing\ModelTestCase;
use Maghead\Schema\DeclareSchema;

use Ramsey\Uuid\Uuid;

class TestUUIDSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('uuid', 'uuid');
    }
}

class UUIDColumnTest extends ModelTestCase
{
    public function models()
    {
        return [
            new TestUUIDSchema
        ];
    }

    public function testCreate()
    {
        $uuid = Uuid::uuid4();
        // var_dump($uuid->toString());
        $ret = TestUUID::create(['uuid' => $uuid->getBytes()]);
        $this->assertResultSuccess($ret);

        $a = TestUUID::load(['uuid' => $uuid->getBytes()]);
        $this->assertEquals($uuid->getBytes(), $a->getUuid());
    }
}
