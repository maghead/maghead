<?php
use TestApp\Model\User;
use Maghead\Testing\ModelTestCase;
use AuthorBooks\Model\Book;

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

        $user = User::defaultRepo()->load($ret->key);
        ok($user);

        $ret = Book::create(array(
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'created_by'   => $user->id,
        ));
        $this->assertResultSuccess($ret);
    }

}
