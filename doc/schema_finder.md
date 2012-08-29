Schema Finder
==============

API:

    $finder = new LazyRecord\Schema\SchemaFinder;
    $finder->addPath( 'tests/schema/' );
    $finder->loadFiles();
    $classes = $finder->getSchemas();

    foreach( $finder as $class ) {
        $class; // schema class.
    }


