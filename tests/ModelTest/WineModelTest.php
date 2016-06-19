<?php
use LazyRecord\Testing\ModelTestCase;

class WineModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'TestApp\Model\\WineSchema',
            'TestApp\Model\\WineCategorySchema',
        );
    }


    /**
     * @basedata false
     */
    public function testWineRecordCreate()
    {
        $record = new \TestApp\Model\Wine;
        $ret = $record->create(array( 'name' => 'Wine Name' ));
        $this->assertResultSuccess($ret);
    }

    /**
     * @basedata false
     */
    public function testWineCategoryAndRefer()
    {
        $c = new \TestApp\Model\WineCategory;
        $record = new \TestApp\Model\Wine;

        is('wines',$record->getSchema()->getTable() );

        $ret = $c->create(array( 'name' => 'Wine Category' ));
        $this->assertResultSuccess($ret);

        $ret = $record->create(array( 'name' => "Wine Item" , 'category_id' => $c->id ));
        $this->assertResultSuccess($ret);
        
        ok($record->category->id, 'the belongsTo should be generated from refer attribute');
        ok($record->category_id,'the original column');
        is('Wine Category',$record->display('category'));
    }

}
