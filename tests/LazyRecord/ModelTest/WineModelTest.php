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

        $ret = $record->create(array( 'name' => 'Wine Name' , 'category_id' => $c->id ));
        result_ok($ret);

        ok($record->category->id, 'the belongsTo should be generated from refer attribute');
        ok($record->category_id,'the original column');
    }

}
