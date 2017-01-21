Schema Finder
==============

API:

    $finder = new LazyRecord\Schema\SchemaFinder;
    $finder->addPath( 'tests/' );
    $finder->load();
    $classes = $finder->getSchemas();

    foreach( $finder as $class ) {
        $class; // schema class.
    }


