<?php

namespace AuthorBooks\Tests;

use Maghead\TableBuilder;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\Address;
use AuthorBooks\Model\AuthorCollection;
use Maghead\Testing\ModelTestCase;
use Maghead\Exporter\CSVExporter;
use Maghead\Importer\CSVImporter;

class AuthorFactory
{
    public static function create($name)
    {
        return Author::createAndLoad(array(
            'name' => $name,
            'email' => 'temp@temp' . rand(),
            'identity' => rand(),
            'confirmed' => true,
        ));
    }
}

class AuthorCollectionTest extends ModelTestCase
{
    public function models()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\BookSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\AddressSchema,
        ];
    }

    public function testCollectionAsPairs()
    {
        $address = new \AuthorBooks\Model\Address;
        $results = array();
        $results[] = $ret = $address->create(array( 'address' => 'Hack' ));
        $this->assertResultSuccess($ret);
        $this->assertResultSuccess($results[] = $address->create(array( 'address' => 'Hack I' )));
        $this->assertResultSuccess($results[] = $address->create(array( 'address' => 'Hack II' )));

        $addresses = new \AuthorBooks\Model\AddressCollection;
        $pairs = $addresses->asPairs('id', 'address');
        $this->assertNotEmpty($pairs);

        // Run update
        $addresses->where(array('address' => 'Hack'));
        $ret = $addresses->update(array('address' => 'BooBoo'));
        $this->assertResultSuccess($ret);
        foreach ($results as $result) {
            $id = $result->id;
            $this->assertNotNull($id);
            $this->assertTrue(isset($pairs[$id]));
            $address = Address::load($result->id);
            $address->delete();
        }
    }


    public function testRepoFetch()
    {
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book I' )));
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book II' )));
        $books = Book::masterRepo()->select('*')->fetch();
        $this->assertCount(2, $books);
    }


    public function testRepoDeleteQueryWithEqualCondition()
    {
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 1' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 3' ]));

        $q = Book::masterRepo()->delete();
        list($ret, $stm) = $q->where()->equal('title', 'Book 1')->execute(); // This tests the fallback method dispatch on condition class.
        $this->assertTrue($ret);

        $books = Book::masterRepo()->select()->fetch();
        $this->assertCount(2, $books);
    }


    public function testRepoUpdateQueryWithoutCondition()
    {
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 1' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 3' ]));

        list($ret, $stm) = Book::masterRepo()->update([ 'title' => 'Updated' ])->execute();
        $this->assertTrue($ret);

        $titles = Book::masterRepo()->select('DISTINCT title')->fetchColumn(0);
        $this->assertCount(1, $titles);
        foreach ($titles as $title) {
            $this->assertEquals('Updated', $title);
        }
    }

    public function testRepoFetchColumn()
    {
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 1' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));

        // create one book with duplicated title
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));

        $titles = Book::masterRepo()->select('DISTINCT title')->fetchColumn(0);
        $this->assertCount(2, $titles);

        foreach ($titles as $title) {
            $this->assertStringMatchesFormat('Book %i', $title);
        }
    }

    public function testRepoFetchQueryColumn()
    {
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 1' ]));
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));

        // create one book with duplicated title
        $this->assertResultSuccess(Book::create([ 'title' => 'Book 2' ]));

        $titles = Book::masterRepo()->select('DISTINCT title')->fetchColumn(0);
        $this->assertCount(2, $titles);

        foreach ($titles as $title) {
            $this->assertStringMatchesFormat('Book %i', $title);
        }
    }




    public function testCollectionReset()
    {
        $book = new Book;
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book I' )));
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book II' )));

        $books = new \AuthorBooks\Model\BookCollection;
        $books->fetch();
        $this->assertEquals(2, $books->size());

        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book III' )));
        $books->reset();
        $books->fetch();
        $this->assertEquals(3, $books->size());
    }


    /**
     * @rebuild false
     */
    public function testClone()
    {
        $authors = new AuthorCollection;
        $authors->fetch();

        $clone = clone $authors;
        $this->assertTrue($clone !== $authors);
        $this->assertNotSame($clone->getCurrentQuery(), $authors->getCurrentQuery());
    }

    public function testCloneWithQuery()
    {
        $a = new Address;
        $ret = Address::create(array('address' => 'Cindy'));
        $this->assertResultSuccess($ret);

        $ret = Address::create(array('address' => 'Hack'));
        $this->assertResultSuccess($ret);

        $addresses = new \AuthorBooks\Model\AddressCollection;
        $addresses->where()->equal('address', 'Cindy');
        $addresses->fetch();
        $this->assertEquals(1, $addresses->size());

        $sql1 = $addresses->toSQL();

        $clone = clone $addresses;
        $sql2 = $clone->toSQL();

        $this->assertEquals($sql1, $sql2);
        $this->assertEquals(1, $clone->size());

        $clone->free();
        $clone->where()
            ->equal('address', 'Hack');
        $this->assertEquals(0, $clone->size());
    }

    public function testIterator()
    {
        $authors = new AuthorCollection;
        foreach ($authors as $a) {
            $this->assertNotNull($a->id);
        }
    }

    public function testBooleanCondition()
    {
        $a = new Author;
        $ret = Author::create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'aaa',
            'confirmed' => false,
        ));
        $this->assertResultSuccess($ret);

        $ret = Author::create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'bbb',
            'confirmed' => true,
        ));
        $this->assertResultSuccess($ret);

        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', false);
        $ret = $authors->fetch();
        $this->assertResultSuccess($ret);
        $this->assertEquals(1, $authors->size());


        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', true);
        $ret = $authors->fetch();
        $this->assertResultSuccess($ret);
        $this->assertEquals(1, $authors->size());
    }

    public function testCollection()
    {
        $author = new Author;
        foreach (range(1, 3) as $i) {
            $ret = Author::create(array(
                'name' => 'Bar-' . $i,
                'email' => 'bar@bar' . $i,
                'identity' => 'bar' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK(true, $ret);
        }

        foreach (range(1, 20) as $i) {
            $ret = Author::create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK(true, $ret);
        }

        $authors2 = new AuthorCollection;
        $authors2->where()
                ->like('name', 'Foo');
        $this->assertCount(20, $authors2);

        $authors = new AuthorCollection;
        $authors->where()->like('name', 'Foo');

        $items = $authors->items();
        $this->assertEquals(20, $authors->size());

        $this->assertTrue(is_array($items));
        foreach ($items as $item) {
            $this->assertNotNull($item->id);
            $this->assertInstanceOf('AuthorBooks\Model\Author', $item);

            $ret = $item->delete();
            $this->assertTrue($ret->success);
        }
        $size = $authors->free()->size();
        $this->assertEquals(0, $size);

        {
            $authors = new AuthorCollection;
            foreach ($authors as $a) {
                $a->delete();
            }
        }
    }

    public function testJoin()
    {
        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address, 'LEFT', 'a');
        $authors->fetch();
        $sql = $authors->toSQL();
        $this->assertRegExp('/LEFT JOIN addresses AS a ON \(m.id = a.author_id\)/', $sql);
    }

    public function testJoinWithAliasAndRelationId()
    {
        $author = AuthorFactory::create('John');
        $author->addresses[] = array( 'address' => 'Address I' );
        $author->addresses[] = array( 'address' => 'Address II' );

        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address, 'LEFT', 'a', 'addresses');
        $authors->fetch();
        $sql = $authors->toSQL();
        $this->assertEquals($sql, $sql);

        $size = $authors->size();
        $this->assertEquals(2, $size);
        foreach ($authors as $a) {
            $this->assertNotNull($a->a_address);
            $this->assertNotNull($a->a_id);
        }
    }

    /**
     * @rebuild false
     */
    public function testJoinWithAliasAndWithoutRelationId()
    {
        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address, 'LEFT', 'a');
        $authors->fetch();
        $sql = $authors->toSQL();
        $this->assertNotNull($sql);
    }

    /**
     * @rebuild false
     */
    public function testMeta()
    {
        $authors = new AuthorCollection;
        $this->assertEquals('AuthorBooks\Model\AuthorSchemaProxy', $authors::SCHEMA_PROXY_CLASS);
        $this->assertEquals('AuthorBooks\Model\Author', $authors::MODEL_CLASS);
    }


    public function testFilter()
    {
        $book = new Book;
        $results = [];
        $this->assertResultSuccess($results[] = Book::create([ 'title' => 'My Book I' ]));
        $this->assertResultSuccess($results[] = Book::create([ 'title' => 'My Book II' ]));
        $this->assertResultSuccess($results[] = Book::create([ 'title' => 'Perl Programming' ]));
        $this->assertResultSuccess($results[] = Book::create([ 'title' => 'My Book IV' ]));

        $books = new BookCollection;
        $this->assertCount(4, $books);

        $perlBooks = $books->filter(function ($item) {
            return $item->title == 'Perl Programming';
        });

        $this->assertEquals(1, $perlBooks->size());
        $this->assertCount(1, $perlBooks->items());

        foreach ($results as $result) {
            $this->assertNotNull($result->key);
            Book::deleteByPrimaryKey($result->id);
        }

        $someBooks = $books->splice(0, 2);
        $this->assertCount(2, $someBooks);
    }

    public function testCollectionDeleteUnconfirmedAuthors()
    {
        // Create confirmed authors
        foreach (range(1, 5) as $i) {
            $ret = Author::create([
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ]);
            $this->assertResultSuccess($ret);
        }

        $ret = Author::create([
            'name' => 'SHOULD BE DELETED',
            'email' => 'bar01@foo',
            'identity' => 'bar01',
            'confirmed' => false,
        ]);
        $this->assertResultSuccess($ret);

        $ret = Author::create([
            'name' => 'SHOULD BE DELETED',
            'email' => 'bar02@foo',
            'identity' => 'bar02',
            'confirmed' => false,
        ]);
        $this->assertResultSuccess($ret);

        $authors = new AuthorCollection;
        $authors->where()->is('confirmed', false);
        $authors->delete();

        $authors = new AuthorCollection;
        $this->assertCount(5, $authors);
    }


    public function testCollectionExporter()
    {
        foreach (range(1, 10) as $i) {
            $author = Author::createAndLoad(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            $this->assertNotFalse($author);
            $this->assertTrue($author->isConfirmed(), 'is true');
        }

        @mkdir('tests/tmp', 0755, true);
        $fp = fopen('tests/tmp/authors.csv', 'w');
        $exporter = new CSVExporter($fp);
        $exporter->exportCollection(new AuthorCollection);
        fclose($fp);

        // Clean up
        $authors = new AuthorCollection;
        $authors->delete();

        $fp = fopen('tests/tmp/authors.csv', 'r');
        $importer = new CSVImporter(new Author);
        $importer->importResource($fp);
        fclose($fp);

        $authors = new AuthorCollection;
        $this->assertCount(10, $authors);
    }


    public function testCollectionPagerAndSelection()
    {
        $author = new Author;
        foreach (range(1, 10) as $i) {
            $author = Author::createAndLoad(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            $this->assertTrue($author->isConfirmed(), 'is true');
        }


        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', true);

        foreach ($authors as $author) {
            $this->assertTrue($author->isConfirmed());
        }
        $this->assertEquals(10, $authors->size());

        /* page 1, 10 per page */
        $pager = $authors->pager(1, 10);

        $pager = $authors->pager();
        $this->assertNotNull($pager->items());
        $this->assertCount(10, $pager->items());


        $array = $authors->toArray();
        $this->assertNotNull($array[0]);
        $this->assertNotNull($array[9]);

        $this->assertCount(10, $authors->items());
        foreach ($authors as $a) {
            $ret = $a->delete();
            $this->assertResultSuccess($ret);
        }
    }
}
