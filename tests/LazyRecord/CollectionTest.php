<?php
    use LazyRecord\SchemaSqlBuilder;

class CollectionTest extends PHPUnit_Framework_TestCase
{

    function setUp()
    {
        \LazyRecord\ConnectionManager::getInstance()->free();
        \LazyRecord\QueryDriver::getInstance()->free();
    }

	function getLogger()
	{
		return new TestLogger;
	}

    function pdoQueryOk($dbh,$sql)
    {
		$ret = $dbh->query( $sql );

		$error = $dbh->errorInfo();
		if($error[1] != null ) {
            throw new Exception( 
                var_export( $error, true ) 
                . ' SQL: ' . $sql 
            );
		}
        // ok( $error[1] != null );
        return $ret;
    }

    function test()
    {
        $connM = \LazyRecord\ConnectionManager::getInstance();
        $connM->addDataSource('default',array(
            'dsn' => 'sqlite::memory:'
        ));

        $dbh = $connM->getDefault();
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );


		$generator = new \LazyRecord\SchemaGenerator;
		$generator->addPath( 'tests/schema/' );
		$generator->setLogger( $this->getLogger() );
		$generator->setTargetPath( 'tests/build/' );
		$classMap = $generator->generate();
        ok( $classMap );

        /*******************
         * build schema 
         * ****************/
		$authorschema = new \tests\AuthorSchema;
		$authorbook = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;
		ok( $authorschema );

		$sql = $builder->build($authorschema);
		ok( $sql );
        // var_dump( $sql ); 
        $this->pdoQueryOk( $dbh , $sql );


		ok( $authorbook );
		$sql = $builder->build($authorbook);
		ok( $sql );
        // var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );


		ok( $bookschema );
		$sql = $builder->build($bookschema);
		ok( $sql );
        // var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );


        $author = new \tests\Author;
        foreach( range(1,20) as $i ) {
            $ret = $author->create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            ok( $ret->success );
        }

        $authors = new \tests\AuthorCollection;

        $q = $authors->createQuery();
        ok( $q );


        ok( $authors::schema_proxy_class );
        ok( $authors::model_class );

        is( 20, $authors->size() ); 

        $cnt = 0;
        foreach( $authors as $author ) {
            $cnt++;
            ok( $author->id );
            is( $cnt , $author->id );
        }

        is( 20, $cnt );

        $authors = new \tests\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed' , true );

        foreach( $authors as $author ) {
            ok( $author->confirmed );
        }




    }
}

