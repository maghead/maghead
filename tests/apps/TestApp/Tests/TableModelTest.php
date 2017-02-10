<?php
use Maghead\Testing\ModelTestCase;
use TestApp\Model\Table;

/**
 * @group app
 */
class TableModelTest extends ModelTestCase
{
    public function getModels()
    {
        return [new TestApp\Model\TableSchema);
    }

    /**
     * @basedata false
     */
    public function testCreate()
    {
        $table = new Table;
        $ret = $table->create(array(
            'columns' => array('c1', 'c2'),
            'rows' => [
                ['foo', 'bar'],
            ],
        ));
        $this->assertResultSuccess($ret, 'Table Create results success');
        $table = Table::masterRepo()->load($ret->key);

        $ret = $table->update(array(
            'columns' => array('b1', 'b2'),
            'rows' => [['zoo', 'kaa']]
        ));
        $this->assertResultSuccess($ret);

        // is(array('b1', 'b2'), $table->columns);
        ok($ret->key);
        ok($ret->success);

        $table = Table::load($ret->key);
        $this->assertNotEmpty($table->get('columns'));
        $this->assertNotEmpty($table->get('rows'));

        $this->assertSame(['b1', 'b2'], $table->get('columns'));
        $this->assertSame([['zoo', 'kaa']], $table->get('rows'));

        $this->assertTrue(is_array($table->getColumns()));
        $this->assertTrue(is_array($table->getRows()));
    }
}
