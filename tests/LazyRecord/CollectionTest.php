<?php
use LazyRecord\SqlBuilder;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use LazyRecord\Testing\ModelTestCase;
use TestApp\Model\NameSchema;
use TestApp\Model\Name;
use TestApp\Model\NameCollection;

class CollectionTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return ['TestApp\Model\NameSchema'];
    }

    public function testCreateRecordWithBooleanFalse()
    {
        $name = new Name;
        $ret = $name->create(array( 
            'name' => 'Foo',
            'confirmed' => false,
            'country' => 'Tokyo',
        ));
        $this->assertResultSuccess($ret);
        $this->assertFalse($name->confirmed);
    }

    public function testBooleanTypeCRUD()
    {
        $name = new Name;
        $ret = $name->create(array( 
            'name' => 'Foo',
            'confirmed' => false,
            'country' => 'Tokyo',
        ));
        $this->assertResultSuccess($ret);
        $this->assertFalse($name->confirmed);

        $ret = $name->load( array( 'name' => 'Foo' ));
        $this->assertResultSuccess($ret);
        $this->assertFalse($name->confirmed);

        $ret = $name->update(array( 'confirmed' => true ) );
        $this->assertResultSuccess($ret);
        $this->assertTrue($name->confirmed);

        $ret = $name->update(array( 'confirmed' => false ) );
        $this->assertResultSuccess($ret);
        $this->assertFalse($name->confirmed);

        $ret = $name->delete();
        $this->assertResultSuccess($ret);

    }

    public function testCollectionGroupBy()
    {
        $name = new Name;
        for($i = 0 ; $i < 5 ; $i++) {
            $ret = $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ));
            $this->assertResultSuccess($ret);
        }

        $names = new NameCollection;
        $names->select( 'name' )->where()
            ->equal('name','Foo');
        $names->groupBy(['name','address']);

        $this->assertCollectionSize(1, $names);
        $items = $names->items();

        $this->assertNotEmpty($items);

        is('Foo', $items[0]->name);
    }


}

