<?php
require_once 'tests/schema/tests/AuthorBooks.php';
use Lazy\SchemaSqlBuilder;

class PgsqlModelTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        Lazy\QueryDriver::free();
        Lazy\ConnectionManager::getInstance()->free();
        Lazy\ConnectionManager::getInstance()->addDataSource('default', array( 
            'dsn' => 'pgsql:dbname=lazy_test',
            'query_options' => array( 
                'quote_column' => true, 
                'quote_table' => true 
            )
        ));
    }

	function getLogger()
	{
		return new TestLogger;
	}

    function buildSchema($dbh,$builder,$schema)
    {
        ok( $schema );
		$sqls = $builder->build($schema);
		ok( $sqls );
        foreach( $sqls as $sql ) {
            ok( $sql );
            $dbh->query( $sql );
        }
    }

	function testCRUD()
	{
        $driver = Lazy\ConnectionManager::getInstance()->getQueryDriver('default');
        $dbh = Lazy\ConnectionManager::getInstance()->getConnection('default');

        $builder = new SchemaSqlBuilder('pgsql', $driver );
		ok( $builder );

        $finder = new Lazy\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema/' );
        $finder->loadFiles();

		$generator = new \Lazy\Schema\SchemaGenerator;
		$generator->setLogger( $this->getLogger() );

		$classMap = $generator->generate( $finder->getSchemaClasses() );
        ok( $classMap );

        /*******************
         * build schema 
         * ****************/
		$authorschema = new \tests\AuthorSchema;
		$authorbook = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;

        $this->buildSchema( $dbh,$builder, $authorschema );
        $this->buildSchema( $dbh,$builder, $authorbook );
        $this->buildSchema( $dbh,$builder, $bookschema );


        /****************************
         * Basic CRUD Test 
         * **************************/
        $author = new \tests\Author;
        ok( $author->_schema );

        $ret = $author->create(array());
        ok( $ret );
        ok( ! $ret->success );
        ok( $ret->message );
        is( 'Empty arguments' , $ret->message );




        $book = new \tests\Book;
        $ret = $book->create(array( 
            'title' => 'title',
            'subtitle' => 'subtitle',
        ));
        ok( $book->id );
        ok( $ret->success );
        ok( $book->delete()->success );


        $ret = $book->create(array( 
            'title' => 'ti--string--tle--\'q"qq',
            'subtitle' => 'subtitle',
        ));
        ok( $book->id );
        ok( $ret->success );
        ok( $ret = $book->delete() );
        ok( $ret->success );




        $query = $author->createQuery();
        ok( $query );

        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        ok( $ret );

        // sqlite does not support last_insert_id: ok( $ret->id ); 
        ok( $ret->success );
        ok( $ret->id );
        is( 1 , $ret->id );
        ok( $author->created_on );
        ok( $author->email );

        $ret = $author->load(1);
        ok( $ret->success );

        is( 1 , $author->id );

        
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array( 'name' => 'Bar' ));
        ok( $ret->success );
        

        is( 'Bar', $author->name );

        $ret = $author->delete();
        ok( $ret->success );

        /**
         * Static CRUD Test 
         */
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $id = $record->id );
        ok( $record->_result->id );
        ok( $record->_result->success );

        $record = \tests\Author::load( (int) $record->_result->id );
        ok( $record );
        ok( $record->id );

        $record = \tests\Author::load( array( 
            'id' => $id
        ));

        ok( $record );
        ok( $record->id );
        

        /**
         * Which runs:
         *    UPDATE authors SET name = 'Rename' WHERE name = 'Mary'
         */
        $ret = \tests\Author::update(array( 'name' => 'Rename' ))
            ->where()
                ->equal('name','Mary')
                ->back()
                ->execute();
        ok( $ret->success );


        $ret = \tests\Author::delete()
            ->where()
                ->equal('name','Rename')
            ->back()->execute();
        ok( $ret->success );
	}
}

