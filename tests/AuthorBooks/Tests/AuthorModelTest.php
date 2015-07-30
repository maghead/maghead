<?php
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;

class AuthorModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AddressSchema,
        ];
    }

    public function tearDown() 
    {
        // Clean up all author records
        $authors = new AuthorCollection;
        foreach ($authors as $author) {
            $author->delete();
        }
    }

    public function testCollection()
    {
        $author = new Author;
        $this->assertResultSuccess($author->create(array( 
            'name' => 'FooBar',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        )));
        $collection = $author->asCollection();
        $this->assertNotNull($collection);
        $this->assertInstanceOf('LazyRecord\BaseCollection',$collection);
        $this->assertResultSuccess($author->delete());
    }


    public function testSchemaInterface()
    {
        $author = new Author;

        $names = array('updated_on','created_on','id','name','email','identity','confirmed');
        foreach( $author->getColumnNames() as $n ) {
            // $this->assertContains($n, $names);

            $this->assertTrue( in_array( $n , $names ));
            $column = $author->getColumn( $n );
            $this->assertInstanceOf('LazyRecord\Schema\RuntimeColumn', $column);
        }

        $columns = $author->getColumns();
        $this->assertCount(7, $columns);

        $columns = $author->getColumns(true); // with virtual column 'v'
        $this->assertCount(8, $columns);

        $this->assertEquals('authors', $author->getTable() );
        $this->assertEquals('Author', $author->getLabel() );
        $this->assertInstanceOf('AuthorBooks\Model\AuthorCollection', $author->newCollection() );
    }

    /**
     * @basedata false
     */
    public function testBooleanCondition() 
    {
        $a = new Author;
        $ret = $a->create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->assertResultSuccess($ret);
        var_dump($ret);

        $ret = $a->create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->assertResultSuccess($ret);
        var_dump( $ret );


        $connManager = \LazyRecord\ConnectionManager::getInstance();
        $dbh = $connManager->getConnection('default');
        $stm = $dbh->query('SELECT * FROM authors WHERE confirmed = 0');
        echo "Authors:\n";
        var_dump($stm->fetchAll());

        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', false);
        $ret = $authors->fetch();
        $this->assertInstanceOf('LazyRecord\Result', $ret);
        $this->assertCollectionSize(1, $authors);
        $this->assertFalse($authors[0]->confirmed);

        $authors = new AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        $this->assertInstanceOf('LazyRecord\Result', $ret);
        $this->assertCollectionSize(1, $authors);
        $this->assertTrue($authors[0]->confirmed);

        $authors->delete();
    }

    /**
     * @basedata false
     */
    public function testVirtualColumn() 
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Pedro' , 
            'email' => 'pedro@gmail.com' , 
            'identity' => 'id',
        ));
        ok($ret->success);

        ok( $v = $author->getColumn('v') ); // virtual colun
        ok( $v->virtual );

        $columns = $author->getSchema()->getColumns();
        ok( ! isset($columns['v']) );

        is('pedro@gmail.compedro@gmail.com',$author->get('v'));

        ok( $display = $author->display( 'v' ) );

        $authors = new AuthorCollection;
        ok($authors);
    }

    /**
     * @basedata false
     */
    public function testStringContainsQuotes()
    {
        $a = new Author;
        $ret = $a->create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        $this->assertResultSuccess($ret);
        ok($a->id);
    }

    /**
     * @basedata false
     */
    public function testCreateWithAnEmptyArrayShouldFail()
    {
        $a = new Author;
        $ret = $a->create(array());
        $this->assertResultFail($ret);
        like('/Empty arguments/' , $ret->message );
    }


    /**
     * @basedata false
     */
    public function testFindAnInexistingRecord()
    {
        $a = new Author;
        $ret = $a->find(array( 'name' => 'A record does not exist.'));
        $this->assertResultFail($ret);
        ok(! $a->id);
    }

    public function testFindInexistingRecord()
    {
        $a2 = new Author;
        $ret = $a2->find(array( 'name' => 'A record does not exist.'));
        $this->assertResultFail($ret);
        ok(! $a2->id);
    }


    public function testCreateRecordWithEscapedString()
    {
        $a2 = new Author;
        $ret = $a2->create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        $this->assertResultSuccess($ret);
        ok( $a2->id );
    }

    public function testCreateRecordWithEmptyArgument()
    {
        $author = new Author;
        $ret = $author->create(array());
        $this->assertResultFail($ret);
        ok($ret->message);
        like('/Empty arguments/' , $ret->message );
    }

    /**
     * Basic CRUD Test 
     */
    public function testBasicCRUDOperations()
    {
        $author = new Author;
        $a2 = new Author;

        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        $this->assertResultSuccess($ret);
        ok( $id = $ret->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );

        $ret = $author->load( $id );
        $this->assertResultSuccess($ret);
        $this->assertEquals($id , $author->id );
        $this->assertEquals('Foo', $author->name);
        $this->assertEquals('foo@google.com', $author->email);
        $this->assertEquals(false , $author->confirmed );

        $ret = $author->find(array( 'name' => 'Foo' ));
        $this->assertResultSuccess($ret);
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array('name' => 'Bar'));
        $this->assertResultSuccess($ret);

        is('Bar', $author->name);

        $ret = $author->delete();
        $this->assertResultSuccess($ret);

        $data = $author->toArray();
        $this->assertEmpty($data);
    }

    public function testMixinMethods() 
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'testMixinMethods',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $age = $author->getAge();
        $this->assertInstanceOf('DateInterval', $age);
        ok($age->format('%s seconds'));
    }

    public function testToArray()
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'testToArray',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $array = $author->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('identity', $array);
    }

    public function testToArrayWithFields() 
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'testToArray',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $array = $author->toArray([ 'name', 'email' ]);

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayNotHasKey('identity', $array);
    }

    /**
     * @basedata false
     */
    public function testRelationshipWithPredefinedConditions()
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Address Testing',
            'email' => 'tom@address',
            'identity' => 'tom-has-two-addresses',
        ));
        $author->addresses[] = array( 'address' => 'Using address', 'unused' => false );
        $author->addresses[] = array( 'address' => 'Unused address', 'unused' => true );

        $addresses = $author->addresses;
        $this->assertCollectionSize(2, $addresses);

        $unusedAddresses = $author->unused_addresses;
        $this->assertCollectionSize(1, $unusedAddresses);

        $this->assertInstanceOf('LazyRecord\BaseModel', $unusedAddresses[0]);
        $this->assertTrue($unusedAddresses[0]->unused);
    }


    public function testUpdateNull()
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);

        $id = $author->id;

        $this->assertResultSuccess( $author->update(array( 'name' => 'I' )) );
        is($id , $author->id );
        is('I', $author->name );

        $ret = $author->update(array( 'name' => null ));
        $this->assertResultSuccess($ret);
        $this->assertEquals($id , $author->id);
        $this->assertNull($author->name, 'updated name should be null');

        $author = new Author;
        $ret = $author->load($id);
        $this->assertResultSuccess($ret);
        $this->assertEquals($id , $author->id);
        $this->assertNull($author->name, 'loaded name should be null');
    }
}
