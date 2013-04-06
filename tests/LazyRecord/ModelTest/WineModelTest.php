<?php

class WineModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'tests\\Wine',
            'tests\\WineCategory',
        );
    }

    public function testWineRecordCreate()
    {
        $record = new \tests\Wine;
        ok($record);
        $ret = $record->create(array( 'name' => 'Wine Name' ));
        result_ok($ret);
    }

    public function testWineCategoryAndRefer()
    {
        $c = new \tests\WineCategory;
        ok($c,'category');
        $record = new \tests\Wine;
        ok($record);

        $ret = $c->create(array( 'name' => 'Wine Category' ));
        result_ok($ret);

        $ret = $record->create(array( 'name' => "Wine Item" , 'category_id' => $c->id ));
        result_ok($ret);
        
        ok($record->category->id, 'the belongsTo should be generated from refer attribute');
        ok($record->category_id,'the original column');
        is('Wine Category',$record->display('category'));
    }


    public function testJoinedColumnExtractionFromCollection() {
        $c = new \tests\WineCategory;
        ok($c,'category');

        $record = new \tests\Wine;
        ok($record);

        $ret = $c->create(array( 'name' => 'Wine Category' ));
        result_ok($ret);

        foreach(  range(1,1000) as $i ) {
            $ret = $record->create(array( 'name' => "Wine Name $i" , 'category_id' => $c->id ));
            result_ok($ret);
        }


        ok( $collection = new \tests\WineCollection );
        $collection->join( new \tests\WineCategory ); // join the WineCategory


        // test query
        foreach( $collection as $item ) {
            ok($item->id);
            // print_r($data);
            ok($item->category,'get category object');
            ok($item->category->id, 'get category id');
            ok($item->category->name, 'get category name');

            $data = $item->getData();
            ok( isset($data['category']) );
            $category = $data['category'];
            ok($category->id);
            same_ok($item->category, $category );
        }
    }
}
