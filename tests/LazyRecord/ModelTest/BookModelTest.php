<?php
use SQLBuilder\Raw;
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Book;

class BookModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 'AuthorBooks\Model\BookSchema' );
    }

    /**
     * @rebuild false
     */
    public function testImmutableColumn()
    {
        $b = new Book ;
        // $b->autoReload = false;
        $ret = $b->create(array( 'isbn' => '123123123' ));

        $this->assertResultSuccess($ret);

        $ret = $b->update(array('isbn'  => '456456' ));
        $this->assertResultFail($ret, 'Should not update immutable column');

        $this->successfulDelete($b);
    }


    /**
     * TODO: Should we validate the field ? think again.
     *
     * @rebuild false
     * @expectedException LazyRecord\QueryException
     */
    public function testUpdateUnknownColumn()
    {
        $b = new Book ;
        // Column not found: 1054 Unknown column 'name' in 'where clause'
        $b->find(array('name' => 'LoadOrCreateTest'));
    }

    /**
     * @rebuild false
     */
    public function testFlagHelper() {
        $b = new Book ;
        $b->create([ 'title' => 'Test Book' ]);

        $schema = $b->getSchema();
        ok($schema);

        $cA = $schema->getColumn('is_hot');
        $cB = $schema->getColumn('is_selled');
        ok($cA);
        ok($cB);

        $ret = $b->update([ 'is_hot' => true ]);
        result_ok( $ret );

        $ret = $b->update([ 'is_selled' => true ]);
        result_ok( $ret );

        $b->delete();
    }

    /**
     * @rebuild false
     */
    public function testTraitMethods() {
        $b = new Book ;
        $this->assertSame(['link1', 'link2'], $b->getLinks());
        $this->assertSame(['store1', 'store2'], $b->getStores());
    }

    public function testInterface() {
        $this->assertInstanceOf('TestApp\ModelInterface\EBookInterface', new Book);
    }

    public function testLoadOrCreate() {
        $results = array();
        $b = new Book ;

        $ret = $b->create(array( 'title' => 'Should Not Load This' ));
        result_ok( $ret );
        $results[] = $ret;

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        result_ok( $ret );
        $results[] = $ret;

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id, $b->id, 'is the same ID');
        $results[] = $ret;


        $b2 = new Book ;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id,$b2->id);
        $results[] = $ret;

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        result_ok($ret);
        ok($b2);
        ok($id != $b2->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3 = new Book ;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        result_ok($ret);
        ok($b3);
        ok($id != $b3->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3->delete();

        foreach( $results as $r ) {
            $book = new Book;
            $book->load($r->id);
            if ($book->id) {
                $book->delete();
            }
        }
    }

    public function testTypeConstraint()
    {
        $book = new Book ;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'publisher_id' => '""',  /* cast this to null or empty */
            // 'publisher_id' => NULL,  /* cast this to null or empty */
        ));


        // FIXME: in sqlite, it works, in pgsql, can not be cast to null
        // ok( $ret->success );
#          print_r($ret->sql);
#          print_r($ret->vars);
#          echo $ret->exception;
    }


    public function testRawSQL()
    {
        $n = new Book ;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );

        $ret = $n->update(array( 
            'view' => new Raw('view + 1')
        ), array('reload' => 1));

        ok( $ret->success );
        is( 1 , $n->view );

        $n->update(array( 
            'view' => new Raw('view + 3')
        ), array('reload' => 1));
        is( 4, $n->view );
    }


    public function testCreateOrUpdateOnTimestampColumn()
    {
        $date = new DateTime;
        $book = new Book;

        $ret = $book->create([ 'title' => 'Create With Time' , 'view' => 0, 'published_at' => $date->format(DateTime::ATOM) ]);
        $this->assertResultSuccess($ret);

        $id = $book->id;
        $this->assertNotNull($id);

        $ret = $book->createOrUpdate([ 'title' => 'Update With Time' , 'view' => 0, 'published_at' => $date->format(DateTime::ATOM) ], [ 'published_at' ]);
        $this->assertResultSuccess($ret);

        $this->assertEquals('Update With Time', $book->title);
        $this->assertEquals($id, $book->id);
    }



    /**
     * @rebuild false
     */
    public function testZeroInflator()
    {
        $b = new Book ;
        $ret = $b->create(array( 'title' => 'Create X' , 'view' => 0 ));
        $this->assertResultSuccess($ret);

        ok( $b->id );
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        $this->assertResultSuccess($ret);
        ok( $b->id );
        is( 0 , $b->view );

        // test incremental
        $ret = $b->update(array( 'view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is( 1,  $b->view );

        $ret = $b->update(array( 'view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is( 2,  $b->view );

        $ret = $b->delete();
        $this->assertResultSuccess($ret);
    }

    /**
     * @rebuild false
     */
    public function testGeneralInterface() 
    {
        $a = new Book;
        ok($a);
        ok( $a->getQueryDriver('default') );
        ok( $a->getWriteQueryDriver() );
        ok( $a->getReadQueryDriver() );
    }
}

