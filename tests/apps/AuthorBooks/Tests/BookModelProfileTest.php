<?php
namespace AuthorBooks\Tests;
use SQLBuilder\Raw;
use Maghead\Testing\ModelProfileTestCase;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookSchema;
use DateTime;
use XHProfRuns_Default;

/**
 * @group profile
 */
class BookModelProfileTest extends ModelProfileTestCase
{
    public function getModels()
    {
        return array(new BookSchema);
    }


    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileLoadByISBN()
    {
        Book::create([
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ]);
        $repo = Book::defaultRepo();
        for ($i = 0 ; $i < $this->N; $i++) {
            $repo->loadByIsbn($uuid);
        }
    }

    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileBooleanColumnAccessor()
    {
        $b = Book::createAndLoad(array(
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
            'published' => false,
        ));
        for ($i = 0 ; $i < $this->N; $i++) {
            $b->isPublished();
        }
    }

    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileStringColumnAccessor()
    {
        $b = Book::createAndLoad(array(
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ));
        for ($i = 0 ; $i < $this->N; $i++) {
            $b->getTitle();
        }
    }

    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileLoadByPrimaryKey()
    {
        $b = Book::createAndLoad([
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ]);
        $bookRepo = Book::defaultRepo();
        for ($i = 0 ; $i < $this->N; $i++) {
            $bookRepo->loadByPrimaryKey($b->id);
        }
    }


    /**
     * @rebuild true
     * @group profile
     */
    public function testProfileLoad()
    {
        $b = new Book;
        $b->create([
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ]);
        $b2 = new Book;
        for ($i = 0 ; $i < $this->N; $i++) {
            $b2->load([ 'isbn' => $uuid ]);
        }

    }



    /**
     * @rebuild true
     * @group profile
     */
    public function testProfileCreate()
    {
        $b = new Book;
        for ($i = 0 ; $i < $this->N; $i++) {
            $b->create(array(
                'title' => "OOP Programming Guide: $i",
                'subtitle' => 'subtitle',
                'isbn' => "123123123$i",
            ));
        }

    }
}
