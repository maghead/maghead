<?php

class WineModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'TestApp\Model\\WineSchema',
            'TestApp\Model\\WineCategorySchema',
        );
    }

    public function testWineRecordCreate()
    {
        $record = new \TestApp\Model\Wine;
        ok($record);
        $ret = $record->create(array( 'name' => 'Wine Name' ));
        result_ok($ret);
    }

    public function testWineCategoryAndRefer()
    {
        $c = new \TestApp\Model\WineCategory;
        ok($c,'category');
        $record = new \TestApp\Model\Wine;
        ok($record);

        is('wines',$record->getSchema()->getTable() );

        $ret = $c->create(array( 'name' => 'Wine Category' ));
        result_ok($ret);

        $ret = $record->create(array( 'name' => "Wine Item" , 'category_id' => $c->id ));
        result_ok($ret);
        
        ok($record->category->id, 'the belongsTo should be generated from refer attribute');
        ok($record->category_id,'the original column');
        is('Wine Category',$record->display('category'));
    }

}
