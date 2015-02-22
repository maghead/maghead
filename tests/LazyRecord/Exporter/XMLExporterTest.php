<?php
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Book ;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use LazyRecord\Exporter\XMLExporter;

class XMLExporterTest extends ModelTestCase
{

    public function getModels()
    {
        return [ 
            'AuthorBooks\Model\BookSchema',
            'AuthorBooks\Model\AuthorSchema',
            'AuthorBooks\Model\AuthorBookSchema',
            'AuthorBooks\Model\AddressSchema',
            'AuthorBooks\Model\PublisherSchema',
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


    public function testExportCollection()
    {
        $book = new Book;
        $ret = $book->create([ 'title' => 'Run & Skate' ]);
        $this->assertResultSuccess($ret);

        $author = new Author;
        $ret = $author->create(array(
            'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' 
        ));
        $this->assertResultSuccess($ret);

        $exporter = new XMLExporter;
        $exporter->exportCollection(new BookCollection);

    }



    public function testRecursiveExporting()
    {
        $author = new Author;
        $ret = $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertResultSuccess($ret);

        // Has Many Relationship
        $author->addresses->create([ 'address' => 'far far away' ]);
        $author->addresses->create([ 'address' => 'taipei 101' ]);
        $author->addresses->create([ 'address' => 'brazil' ]);
        $author->addresses->create([ 'address' => 'san francisco' ]);


        $book = new Book;
        $ret = $book->create([ 
            'title' => 'Run & Skate',
        ]);
        $this->assertResultSuccess($ret);

        // ManyToMany
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

