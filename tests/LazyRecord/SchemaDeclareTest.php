<?php
namespace main;
require_once 'tests/schema/AuthorBooks.php';

class SchemaDeclareTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthor()
    {
        $declare = new \tests\AuthorSchema;

    }

    public function test()
    {
        $declare = new \tests\BookSchema;
        ok( $declare , 'schema ok' );

        ok( $declare->columns , 'columns' );
        ok( $declare->columns );
        ok( $c = $declare->columns['title'] );
        ok( $c = $declare->columns['subtitle'] );
        ok( $c = $declare->columns['description'] );


        is( 'tests\Book' , $declare->getModelClass() );
        is( 'books' , $declare->getTable() );

        $schemaArray = $declare->export();
        ok( $schemaArray );

        $schema = new \LazyRecord\Schema;
        $schema->import( $schemaArray );

        ok( $schema );
    }
}

