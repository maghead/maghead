<?php
use TestApp\Model\User;
use LazyRecord\Testing\ModelTestCase;

class UserModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            new \TestApp\Model\UserSchema,
            new \AuthorBooks\Model\BookSchema,
        );
    }

    /**
     * @basedata false
     */
    public function testRefer()
    {
        $user = new User;
        $ret = $user->create(array( 'account' => 'c9s' ));
        $this->assertResultSuccess($ret);

        $user = User::find($ret->key);
        ok($user->id);

        $book = new \AuthorBooks\Model\Book;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'created_by'   => $user->id,
        ));
        $this->assertResultSuccess($ret);
    }

}
