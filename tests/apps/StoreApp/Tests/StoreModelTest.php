<?php
use Maghead\Testing\ModelTestCase;
use StoreApp\Model\Store;
use StoreApp\Model\StoreCollection;

class StoreModelTest extends ModelTestCase
{
    public function getModels()
    {
        return [new \StoreApp\Model\StoreSchema];
    }

    public function testRequiredField()
    {
        $store = new Store;
        $ret = $store->create([ 'name' => 'testapp', 'code' => 'testapp' ]);
        $this->assertResultSuccess($ret);
    }

    public function testCreateWithRequiredFieldNull()
    {
        $store = new Store;
        $ret = $store->create([ 'name' => 'testapp', 'code' => null ]);
        $this->assertResultFail($ret);
    }

    public function testUpdateWithRequiredFieldNull()
    {
        $store = Store::createAndLoad([ 'name' => 'testapp', 'code' => 'testapp' ]);
        $this->assertNotFalse($store);

        $ret = $store->update([ 'name' => 'testapp', 'code' => null ]);
        $this->assertResultFail($ret);

        $ret = $store->update([ 'name' => 'testapp 2' ]);
        $this->assertResultSuccess($ret);
        $this->assertEquals('testapp 2', $store->name);
    }
}
