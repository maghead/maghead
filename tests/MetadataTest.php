<?php
use LazyRecord\ConnectionManager;
use LazyRecord\Metadata;
use LazyRecord\Model\MetadataSchema;
use LazyRecord\Testing\ModelTestCase;

class MetadataTest extends ModelTestCase
{

    public function getModels()
    {
        return [
            new MetadataSchema,
        ];
    }

    public function testArrayAccessor()
    {
        $metadata = new Metadata($this->conn, $this->queryDriver);
        $metadata->init();
        $metadata['version'] = 1;
        $this->assertEquals(1, $metadata['version']);

        $metadata['version'] = 2;
        $this->assertEquals(2, $metadata['version']);

        $this->assertEquals(2,$metadata->getVersion());
        foreach ($metadata as $key => $value ) {
            $this->assertTrue(!is_numeric($key));
            $this->assertNotNull($value);
        }
    }

    public function testMetadata()
    {
        $metadata = new Metadata($this->conn, $this->queryDriver);
        $metadata->init();

        $metaItem = new \LazyRecord\Model\Metadata;
        $schema = $metaItem->getSchema();
        $this->assertNotNull($schema);

        $ret = $metaItem->create(array('name' => 'version', 'value' => '0.1' ));
        $this->assertResultSuccess($ret);
    }

    public function testCollection()
    {
        $metadata = new Metadata($this->conn, $this->queryDriver);
        $metadata->init();
        $metadata['version'] = 1;
        $metadata['name'] = 'c9s';
        $metas = new LazyRecord\Model\MetadataCollection;
        foreach ($metas as $meta) {
            ok($meta);
        }
    }
}
