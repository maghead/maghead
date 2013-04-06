<?php
use LazyRecord\ConnectionManager;

class MetadataTest extends PHPUnit_Framework_TestCase
{


    function setUp()
    {
        $connm = ConnectionManager::getInstance();
        $connm->addDataSource('default', array( 'dsn' => 'sqlite::memory:' ));
    }

    function tearDown()
    {
        $connm = ConnectionManager::getInstance();
        $connm->removeDataSource('default');
        $connm->close('default');
    }


    function test()
    {
        $metadata = new LazyRecord\Metadata('default');
        ok($metadata);
        $metadata->init();
        $metadata->init();

        $metadata['version'] = 1;
        is(1, $metadata['version']);

        $metadata['version'] = 2;
        is(2, $metadata['version']);

        is(2,$metadata->getVersion());

        foreach( $metadata as $key => $value ) {
            ok($key);
            ok($value);
        }
    }

    public function testMetadata() {
        $metadata = new LazyRecord\Metadata('default');
        ok($metadata);
        $metadata->init();

        $metaItem = new LazyRecord\Model\Metadata;
        ok($metaItem);
        ok($metaItem->schema,"Get schema from magic method");
        ok($metaItem->getSchema(),"Get schema");

        $ret = $metaItem->create(array('name' => 'version', 'value' => '0.1' ));
        ok($ret->success);
    }

    public function testCollection() {
        $metadata = new LazyRecord\Metadata('default');
        ok($metadata);
        $metadata->init();

        $metadata['version'] = 1;
        $metadata['name'] = 'c9s';

        $metas = new LazyRecord\Model\MetadataCollection;
        ok($metas);
        foreach( $metas as $meta ) {
            ok($meta);
        }
    }
}

