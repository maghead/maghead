<?php
use LazyRecord\Testing\ModelTestCase;

class UserModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'TestApp\Model\UserSchema',
            'TestApp\Model\BookSchema'
        );
    }

    /**
     * @basedata false
     */
    public function testRefer()
    {
        $user = new \TestApp\Model\User;
        $ret = $user->create(array( 'account' => 'c9s' ));
        $this->assertResultSuccess($ret);
        ok( $user->id );

        $book = new \TestApp\Model\Book;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'publisher_id' => '""',  /* cast this to null or empty */
            'created_by' => $user->id,
        ));
        $this->assertResultSuccess($ret);
    }

}
