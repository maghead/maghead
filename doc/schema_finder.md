Schema Finder
==============

API:

    $finder = new LazyRecord\Schema\SchemaFinder;
    $finder->addPath( 'tests/' );
    $finder->find();
    $classes = $finder->getSchemas();

    foreach( $finder as $class ) {
        $class; // schema class.
    }


