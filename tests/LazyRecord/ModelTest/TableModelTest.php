<?php
use LazyRecord\Testing\ModelTestCase;
use TestApp\Model\Table;

class TableModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array('TestApp\\Model\\TableSchema');
    }

    public function testCreate() {
        $table = new Table;
        ok($table);
        $ret = $table->create(array( 
            'columns' => array('c1', 'c2'),
            'rows' => array(
                array('foo', 'bar')
            ),
        ));
        result_ok($ret, 'Table Create results success');

        $ret = $table->update(array(
            'columns' => array('b1', 'b2'),
            'rows' => [['zoo', 'kaa']]
        ));
        // is(array('b1', 'b2'), $table->columns);
        ok($ret->id);
        ok($ret->success);
        result_ok($ret);

        $ret = $table->reload();
        result_ok($ret);

        $this->assertNotEmpty($table->get('columns'));
        $this->assertNotEmpty($table->get('rows'));

        same(['b1', 'b2'], $table->get('columns'));
        same([['zoo', 'kaa']], $table->get('rows'));

        $this->assertTrue(is_array($table->columns));
        $this->assertTrue(is_array($table->rows));

        $ret = $table->delete();
        ok($ret->success);
    }
}
