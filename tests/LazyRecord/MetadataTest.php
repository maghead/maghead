<?php
use LazyRecord\ConnectionManager;
use LazyRecord\Metadata;

class MetadataTest extends PHPUnit_Framework_TestCase
{


    public function setUp()
    {
        $connm = ConnectionManager::getInstance();
        $connm->addDataSource('default', array( 'dsn' => 'sqlite::memory:' ));
    }

    public function tearDown()
    {
        $connm = ConnectionManager::getInstance();
        $connm->removeDataSource('default');
        $connm->close('default');
    }


    function test()
    {
        $metadata = new Metadata('default');
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
        $metadata = new Metadata('default');
        $metadata->init();

        $metaItem = new \LazyRecord\Model\Metadata;
        ok($metaItem);
        ok($metaItem->getSchema(),"Get schema");

        $ret = $metaItem->create(array('name' => 'version', 'value' => '0.1' ));
        ok($ret->success);
    }

    public function testCollection() {
        $metadata = new Metadata('default');
        $metadata->init();

        $metadata['version'] = 1;
        $metadata['name'] = 'c9s';

        $metas = new LazyRecord\Model\MetadataCollection;
        foreach( $metas as $meta ) {
            ok($meta);
        }
    }
}

