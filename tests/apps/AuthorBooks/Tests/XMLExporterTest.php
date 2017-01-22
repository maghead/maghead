<?php
use Maghead\Testing\ModelTestCase;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use Maghead\Exporter\XMLExporter;

class XMLExporterTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \AuthorBooks\Model\BookSchema,
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\PublisherSchema,
        ];
    }

    public function testSimpleExport()
    {
        $book = new Book;
        $ret = Book::create([
            'title' => 'Run & Skate',
            'is_hot' => true,
            'is_selled' => true,
        ]);
        $this->assertResultSuccess($ret);

        $exporter = new XMLExporter;
        $dom = $exporter->exportRecord($book);
        $dom->formatOutput = true;
        $this->assertInstanceOf('DOMDocument', $dom);
        $xml = $dom->saveXML();
        $this->assertNotEmpty($xml);
    }


    public function testExportCollection()
    {
        $book = new Book;
        $ret = Book::create([ 'title' => 'Run & Skate' ]);
        $this->assertResultSuccess($ret);

        $author = new Author;
        $ret = Author::create(array(
            'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' 
        ));
        $this->assertResultSuccess($ret);

        $exporter = new XMLExporter;
        $exporter->exportCollection(new BookCollection);

    }



    public function testRecursiveExporting()
    {
        $author = Author::createAndLoad(array(
            'name' => 'Z',
            'email' => 'z@z',
            'identity' => 'z',
            'updated_on' => '2012-01-01 00:00:00',
            'created_on' => '2012-01-01 00:00:00',
        ));

        // Has Many Relationship
        $author->addresses->create([ 'address' => 'far far away' ]);
        $author->addresses->create([ 'address' => 'taipei 101' ]);
        $author->addresses->create([ 'address' => 'brazil' ]);
        $author->addresses->create([ 'address' => 'san francisco' ]);


        $book = Book::createAndLoad([ 
            'title' => 'Run & Skate',
            'published_at' => '2012-01-01 00:00:00',
            'updated_on' => '2012-01-01 00:00:00',
            'created_on' => '2012-01-01 00:00:00',
            'is_selled' => false,
        ]);

        // ManyToMany
        $author->author_books->create([
            'book_id' => $book->id,
            'created_on' => '2012-01-01 00:00:00',
        ]);


        $book = Book::createAndLoad([ 
            'title' => 'Run & Skate II',
            'updated_on' => '2012-01-01 00:00:00',
            'created_on' => '2012-01-01 00:00:00',
            'published_at' => '2012-01-01 00:00:00',
            'is_selled' => false,
        ]);
        $author->author_books->create([
            'book_id' => $book->id,
            'created_on' => '2012-01-01 00:00:00',
        ]);


        $exporter = new XMLExporter;
        $dom = $exporter->exportRecord($author);

        $dom->formatOutput = true;
        $this->assertInstanceOf('DOMDocument', $dom);
        $xml = $dom->saveXML();
        $this->assertNotEmpty($xml);

        file_put_contents('tests/xmlTestRecursiveExporting.actual', $xml);
        $this->assertFileEquals('tests/xmlTestRecursiveExporting.expected', 'tests/xmlTestRecursiveExporting.actual');
    }
}
