<?php
use Maghead\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use Maghead\Migration\Migration;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Driver\PDOPgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;

class AuthorModelTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\BookSchema,
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

    public function testLoadByEmail()
    {
        $author = new Author;
        $this->assertNotFalse($author = Author::createAndLoad(array( 
            'name' => 'FooBar',
            'email' => 'timcook@apple.com',
            'identity' => 'a',
            'confirmed' => false,
        )));
        $timCook = Author::defaultRepo()->loadByEmail('timcook@apple.com');
        $this->assertNotNull($timCook);
        /*
        $timCook = Author::repo('master')->loadByEmail('timcook@apple.com');
        $timCook = Author::repo('slave')->loadByEmail('timcook@apple.com');
        */
    }

    public function testCollection()
    {
        $author = new Author;
        $this->assertNotFalse($author = Author::createAndLoad(array( 
            'name' => 'FooBar',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        )));
        $collection = $author->asCollection();
        $this->assertNotNull($collection);
        $this->assertInstanceOf('Maghead\BaseCollection',$collection);
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
            $this->assertInstanceOf('Maghead\Schema\RuntimeColumn', $column);
        }

        $columns = $author->getColumns();
        $this->assertCount(7, $columns);

        $columns = $author->getColumns(true); // with virtual column
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
        $ret = Author::create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->assertResultSuccess($ret);

        $ret = Author::create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->assertResultSuccess($ret);

        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', false);
        $ret = $authors->fetch();
        $this->assertInstanceOf('Maghead\Result', $ret);
        $this->assertCollectionSize(1, $authors);
        $this->assertFalse($authors[0]->isConfirmed());

        $authors = new AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        $this->assertInstanceOf('Maghead\Result', $ret);
        $this->assertCollectionSize(1, $authors);
        $this->assertTrue($authors[0]->isConfirmed());

        $authors->delete();
    }

    public function testAccessor()
    {
        $author = new Author;
        $ret = Author::create([
            'name' => 'Pedro',
            'email' => 'pedro@gmail.com',
            'identity' => 'id',
            'confirmed' => true,
        ]);
        $this->assertResultSuccess($ret);
        $author = Author::load($ret->key);

        $this->assertEquals('Pedro',$author->getName());
        $this->assertEquals('pedro@gmail.com',$author->getEmail());
        $this->assertEquals(true,$author->isConfirmed());
    }

    /**
     * @basedata false
     */
    public function testStringContainsQuotes()
    {
        $a = new Author;
        $ret = Author::create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        $this->assertResultSuccess($ret);
    }

    /**
     * @basedata false
     */
    public function testCreateWithAnEmptyArrayShouldFail()
    {
        $a = new Author;
        $ret = Author::create(array());
        $this->assertResultFail($ret);
        like('/Empty arguments/' , $ret->message );
    }


    /**
     * @basedata false
     */
    public function testFindAnInexistingRecord()
    {
        $a = Author::load(array( 'name' => 'A record does not exist.'));
        $this->assertFalse($a);
    }

    public function testFindInexistingRecord()
    {
        $a = Author::load(array( 'name' => 'A record does not exist.'));
        $this->assertFalse($a);
    }


    public function testCreateRecordWithEscapedString()
    {
        $a2 = new Author;
        $ret = $a2->create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        $this->assertResultSuccess($ret);
    }

    public function testCreateRecordWithEmptyArgument()
    {
        $author = new Author;
        $ret = Author::create(array());
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

        $ret = Author::create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        $this->assertResultSuccess($ret);
        $author = Author::load($ret->key);
        ok( $id = $ret->key );
        $this->assertEquals('Foo', $author->name );
        $this->assertEquals('foo@google.com', $author->email );

        $author = Author::load( $id );
        $this->assertNotFalse($author);
        $this->assertEquals($id , $author->id );
        $this->assertEquals('Foo', $author->name);
        $this->assertEquals('foo@google.com', $author->email);
        $this->assertEquals(false , $author->isConfirmed() );

        $author = Author::load(array( 'name' => 'Foo' ));
        $this->assertNotFalse($author);
        $this->assertEquals($id , $author->id );
        $this->assertEquals('Foo', $author->name );
        $this->assertEquals('foo@google.com', $author->email );
        $this->assertFalse($author->isConfirmed());

        $ret = $author->update(array('name' => 'Bar'));
        $this->assertResultSuccess($ret);

        $this->assertEquals('Bar', $author->name);

        $ret = $author->delete();
        $this->assertResultSuccess($ret);

        $data = $author->toArray();
    }

    public function testMixinMethods()
    {
        $author = new Author;
        $ret = Author::create(array( 
            'name' => 'testMixinMethods',
            'email' => 'test.user@gmail.com',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $author = Author::load($ret->key);
        $age = $author->getAge();
        $this->assertInstanceOf('DateInterval', $age);
        ok($age->format('%s seconds'));
    }

    public function testToArray()
    {
        $author = new Author;
        $ret = Author::create(array( 
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
        $ret = Author::create(array( 
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
        $ret = Author::create(array( 
            'name' => 'Address Testing',
            'email' => 'tom@address',
            'identity' => 'tom-has-two-addresses',
        ));
        $this->assertResultSuccess($ret);
        $author = Author::load($ret->key);

        $author->addresses[] = array( 'address' => 'Using address', 'unused' => false );
        $author->addresses[] = array( 'address' => 'Unused address', 'unused' => true );

        $addresses = $author->addresses;
        $this->assertCollectionSize(2, $addresses);

        $unusedAddresses = $author->unused_addresses;
        $this->assertCollectionSize(1, $unusedAddresses);

        $this->assertInstanceOf('Maghead\BaseModel', $unusedAddresses[0]);
        $this->assertTrue($unusedAddresses[0]->isUnused());
    }

    public function testLoadForUpdate()
    {
        if (!$this->queryDriver instanceof PDOMySQLDriver) {
            return $this->markTestSkipped('select for update is for mysql driver');
        }

        $author = new Author;
        $ret = Author::create(array(
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);

        $a2 = Author::loadForUpdate([ 'identity' => 'zz3' ]);
        $this->assertNotFalse($a2);

        $ret = $a2->update(['name' => 'Maroon V']);
        $this->assertResultSuccess($ret);
    }

    public function testUpdateNull()
    {
        $author = new Author;
        $ret = Author::create(array(
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);

        $author = Author::load($ret->key);

        $id = $author->id;

        $this->assertResultSuccess($author->update(array( 'name' => 'I' )) );
        is($id , $author->id );
        is('I', $author->name );

        $ret = $author->update(array( 'name' => null ));
        $this->assertResultSuccess($ret);
        $this->assertEquals($id , $author->id);
        $this->assertNull($author->name, 'updated name should be null');

        $author = Author::load($id);
        $this->assertEquals($id , $author->id);
        $this->assertNull($author->name, 'loaded name should be null');
    }


    public function testMigrationRename()
    {
        if ($this->queryDriver instanceof SQLiteDriver) {
            return $this->markTestSkipped('skip this test when sqlite driver is used.');
        }
        $migration = new Migration($this->conn, $this->queryDriver, $this->logger);
        $author = new Author;
        $schema = $author->getDeclareSchema();
        $column = $schema->getColumn('name');
        $newColumn = clone $column;
        $newColumn->name('name2');
        $migration->renameColumn('authors', $column, $newColumn);
        $migration->renameColumn('authors', $newColumn, $column);
    }
}
