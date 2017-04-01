<?php
use TestApp\Model\User;
use Maghead\Testing\ModelTestCase;
use AuthorBooks\Model\Book;

/**
 * @group app
 */
class UserModelTest extends ModelTestCase
{
    public function models()
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
        $ret = User::create([ 'account' => 'c9s' ]);
        $this->assertResultSuccess($ret);

        $user = User::masterRepo()->load($ret->key);
        $this->assertNotNull($user);
        $ret = Book::create(array(
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'created_by'   => $user->id,
        ));
        $this->assertResultSuccess($ret);
    }
}
