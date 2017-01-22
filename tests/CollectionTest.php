<?php
use Maghead\SqlBuilder;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use Maghead\Testing\ModelTestCase;
use TestApp\Model\NameSchema;
use TestApp\Model\Name;
use TestApp\Model\NameCollection;

class CollectionTest extends ModelTestCase
{
    public function getModels()
    {
        return [new \TestApp\Model\NameSchema];
    }

    public function testCollectionGroupBy()
    {
        $name = new Name;
        for($i = 0 ; $i < 5 ; $i++) {
            $ret = $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ));
            $this->assertResultSuccess($ret);
        }

        $names = new NameCollection;
        $names->setSelect('name')->where()
            ->equal('name','Foo');
        $names->groupBy(['name','address']);

        $this->assertCollectionSize(1, $names);
        $items = $names->items();

        $this->assertNotEmpty($items);

        is('Foo', $items[0]->name);
    }


}

