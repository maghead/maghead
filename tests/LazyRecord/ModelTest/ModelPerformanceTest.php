<?php

class ModelPerformanceTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'tests\\WineSchema',
            'tests\\WineCategorySchema',
        );
    }


    public function setUp()
    {
        parent::setUp();
        $this->createRecords();
    }

    public function createRecords() 
    {
        $c = new \tests\WineCategory;
        ok($c,'category');

        $record = new \tests\Wine;
        ok($record);

        $ret = $c->create(array( 'name' => 'Wine Category' ));
        result_ok($ret);

        foreach( range(1,1000) as $i ) {
            $ret = $record->fastCreate(array( 'name' => "Wine Name $i" , 'category_id' => $c->id ));
            result_ok($ret);
        }
    }

    public function testPDOQuerySample()
    {
        $record = new \tests\Wine;
        $table = $record->getSchema()->getTable();
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $pdo = $connManager->get('default');
        foreach( range(1,1000) as $i ) {
            $stm = $connManager->getConnection('default')->prepareAndExecute("select * from $table where id = :id", array('id' => $i));
            $o = $stm->fetchObject();
            ok($o);
        }
    }

    public function testJoinedColumnExtractionFromCollection() 
    {
        ok( $collection = new \tests\WineCollection );
        $collection->join( new \tests\WineCategory ); // join the WineCategory

        // test query
        foreach( $collection as $item ) {
            ok($item->id);
            // print_r($data);
            ok($item->category,'get category object');
            ok($item->category->id, 'get category id');
            ok($item->category->name, 'get category name');
        }
        return $collection;
    }
}
