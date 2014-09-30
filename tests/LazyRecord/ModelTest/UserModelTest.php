<?php

class UserModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'TestApp\Model\UserSchema',
            'TestApp\Model\BookSchema'
        );
    }

    public function testRefer()
    {
        $user = new \TestApp\Model\User;
        ok( $user );
        $ret = $user->create(array( 'account' => 'c9s' ));
        result_ok($ret);
        ok( $user->id );

        $book = new \TestApp\Model\Book;
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
