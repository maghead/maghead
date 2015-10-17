<?php
namespace AuthorBooks\Tests;
use SQLBuilder\Raw;
use LazyRecord\Testing\ModelProfileTestCase;
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
    public function testProfileFindByISBN()
    {
        $b = new Book;
        $b->create(array(
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ));
        $b2 = new Book;
        for ($i = 0 ; $i < $this->N; $i++) {
            $b2->findByIsbn($uuid);
        }
    }


    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileFindByPrimaryKey()
    {
        $b = new Book;
        $b->create(array(
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ));
        $b2 = new Book;
        for ($i = 0 ; $i < $this->N; $i++) {
            $b2->find($b->id);
        }
    }


    /**
     * @rebuild true
     * @group profile
     */
    public function testProfileLoad()
    {
        $b = new Book;
        $b->create(array(
            'title' => "OOP Programming Guide",
            'subtitle' => 'subtitle',
            'isbn' => $uuid = uniqid(),
        ));
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
