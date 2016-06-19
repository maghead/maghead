<?php
namespace main;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\AuthorSchema;

class SchemaDeclareTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthor()
    {
        $declare = new AuthorSchema;

    }

    public function testRuntimeSchemaConstruction()
    {
        $declare = new BookSchema;
        $this->assertNotEmpty( $declare->columns , 'columns' );
        $this->assertNotNull( $c = $declare->columns['title'] );
        $this->assertNotNull( $c = $declare->columns['subtitle'] );
        $this->assertNotNull( $c = $declare->columns['description'] );

        is( 'AuthorBooks\Model\Book' , $declare->getModelClass() );
        is( 'books' , $declare->getTable() );

        $schemaArray = $declare->export();
        ok( $schemaArray );
        ok( is_array($schemaArray) );
    }
}

