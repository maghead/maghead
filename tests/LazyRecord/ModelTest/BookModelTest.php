<?php
class BookModelTest extends PHPUnit_Framework_ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 'tests\BookSchema' );
    }

    public function testLoadOrCreate() 
    {
        $b = new \tests\Book;
        $ret = $b->find( array( 'name' => 'LoadOrCreateTest' ) );
        result_fail( $ret );
        ok( ! $b->id );

        $ret = $b->create(array( 'title' => 'Should Not Load This' ));
        result_ok( $ret );

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        result_ok( $ret );

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id, $b->id, 'is the same ID');


        $b2 = new \tests\Book;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id,$b2->id);

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        result_ok($ret);
        ok($b2);
        ok($id != $b2->id , 'we should create anther one'); 

        $b3 = new \tests\Book;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        result_ok($ret);
        ok($b3);
        ok($id != $b3->id , 'we should create anther one'); 

        $b3->delete();

        foreach( $b2->flushResults() as $r ) {
            result_ok( \tests\Book::delete($r->id)->execute() );
        }
        foreach( $b->flushResults() as $r ) {
            result_ok( \tests\Book::delete($r->id)->execute() );
        }
    }

    public function testTypeConstraint()
    {
        $book = new \tests\Book;
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
        $n = new \tests\Book;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );

        $ret = $n->update(array( 
            'view' => array('view + 1')
        ));

        ok( $ret->success );
        is( 1 , $n->view );

        $n->update(array( 
            'view' => array('view + 3')
        ));
        $ret = $n->reload();
        ok( $ret->success );
        is( 4, $n->view );
    }

    public function testZeroInflator()
    {
        $b = new \tests\Book;
        $ret = $b->create(array( 'title' => 'Create X' , 'view' => 0 ));
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );

        // test incremental
        $ret = $b->update(array( 'view'  => array('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 1,  $b->view );

        $ret = $b->update(array( 'view'  => array('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 2,  $b->view );

        $ret = $b->delete();
        result_ok($ret);
    }

    public function testGeneralInterface() 
    {
        $a = new \tests\Book;
        ok($a);

        ok( $a->getQueryDriver('default') );
        ok( $a->getWriteQueryDriver() );
        ok( $a->getReadQueryDriver() );

        $query = $a->createQuery();
        ok($query);
        isa_ok('SQLBuilder\\QueryBuilder', $query );
    }
}
