<?php

class UserModelTest extends PHPUnit_Framework_ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'tests\UserSchema',
            'tests\BookSchema'
        );
    }

    public function testRefer()
    {
        $user = new \tests\User;
        ok( $user );
        $ret = $user->create(array( 'account' => 'c9s' ));
        result_ok($ret);
        ok( $user->id );

        $book = new \tests\Book;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'publisher_id' => '""',  /* cast this to null or empty */
            'created_by' => $user->id,
        ));
        ok( $ret );

        // XXX: broken
#          ok( $book->created_by );
#          is( $user->id, $book->created_by->id );
#          ok( $user->id , $book->getValue('created_by') );
    }

}
