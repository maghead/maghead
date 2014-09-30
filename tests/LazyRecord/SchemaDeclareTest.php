<?php
namespace main;

class SchemaDeclareTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthor()
    {
        $declare = new \TestApp\AuthorSchema;

    }

    public function testRuntimeSchemaConstruction()
    {
        $declare = new \TestApp\BookSchema;
        ok( $declare , 'schema ok' );

        ok( $declare->columns , 'columns' );
        ok( $declare->columns );
        ok( $c = $declare->columns['title'] );
        ok( $c = $declare->columns['subtitle'] );
        ok( $c = $declare->columns['description'] );

        is( 'TestApp\Book' , $declare->getModelClass() );
        is( 'books' , $declare->getTable() );

        $schemaArray = $declare->export();
        ok( $schemaArray );
        ok( is_array($schemaArray) );

        $schema = new \LazyRecord\Schema\RuntimeSchema;
        $schema->import( $schemaArray );

        ok( $schema );
    }
}

