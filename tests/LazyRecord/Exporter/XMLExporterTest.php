<?php
use LazyRecord\Testing\ModelTestCase;
use TestApp\Model\Book;
use TestApp\Model\Author;
use LazyRecord\Exporter\XMLExporter;

class XMLExporterTest extends ModelTestCase
{

    public function getModels()
    {
        return [ 
            'TestApp\Model\BookSchema',
            'TestApp\Model\AuthorSchema',
            'TestApp\Model\AuthorBookSchema',
            'TestApp\Model\AddressSchema',
            'TestApp\Model\PublisherSchema',
        ];
    }

    public function testSimpleExport()
    {
        $book = new Book;
        $ret = $book->create([ 
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

    public function testRecursiveExporting()
    {
        $author = new Author;
        $ret = $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertResultSuccess($ret);

        $author->addresses->create([ 'address' => 'far far away' ]);
        $author->addresses->create([ 'address' => 'taipei 101' ]);
        $author->addresses->create([ 'address' => 'brazil' ]);
        $author->addresses->create([ 'address' => 'san francisco' ]);


        $book = new Book;
        $ret = $book->create([ 
            'title' => 'Run & Skate',
        ]);
        $this->assertResultSuccess($ret);
        $author->author_books->create([ 'book_id' => $book->id ]);


        $book = new Book;
        $ret = $book->create([ 
            'title' => 'Run & Skate II',
        ]);
        $this->assertResultSuccess($ret);
        $author->author_books->create([ 'book_id' => $book->id ]);


        $exporter = new XMLExporter;
        $dom = $exporter->exportRecord($author);

        $dom->formatOutput = true;
        $this->assertInstanceOf('DOMDocument', $dom);
        $xml = $dom->saveXML();
        $this->assertNotEmpty($xml);

        // echo $xml;
    }


}

