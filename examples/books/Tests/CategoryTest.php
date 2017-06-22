<?php

namespace AuthorBooks\Tests;

use Magsql\Raw;
use Maghead\Testing\ModelTestCase;
use Maghead\Runtime\Result;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\Category;
use AuthorBooks\Model\CategoryCollection;
use AuthorBooks\Model\CategorySchema;
use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\AuthorBookSchema;
use DateTime;

/**
 * @group app
 */
class CategoryTest extends ModelTestCase
{
    public function models()
    {
        return [
            new AuthorSchema,
            new BookSchema,
            new AuthorBookSchema,
            new CategorySchema,
        ];
    }

    public function testFindChildrenRecords()
    {
        $p1 = Category::createAndLoad([
            "name" => "P1",
        ]);

        $cc1 = Category::createAndLoad([
            "name" => "C1",
            "parent_id" => $p1->getKey(),
        ]);

        $cc2 = Category::createAndLoad([
            "name" => "C2",
            "parent_id" => $p1->getKey(),
        ]);

        $b1 = Book::createAndLoad([ 'title' => 'Book1' , 'category_id' => $p1->getKey()  ]);
        $b2 = Book::createAndLoad([ 'title' => 'Book2' , 'category_id' => $cc1->getKey() ]);

        $children = $p1->findChildrenRecords();

        $this->assertArrayHasKey("subcategories", $children);
        $this->assertInstanceOf(CategoryCollection::class, $children["subcategories"]);
        $this->assertCount(2, $children["subcategories"]);
        $this->assertArrayHasKey("books", $children);
        $this->assertInstanceOf(BookCollection::class, $children["books"]);
        $this->assertCount(1, $children["books"]);

        $children = $cc1->findChildrenRecords();
        $this->assertInstanceOf(CategoryCollection::class, $children["subcategories"]);
        $this->assertCount(0, $children["subcategories"]);
        $this->assertInstanceOf(BookCollection::class, $children["books"]);
        $this->assertCount(1, $children["books"]);
    }
}
