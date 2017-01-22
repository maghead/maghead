Schema Finder
==============

API:

    $finder = new Maghead\Schema\SchemaFinder;
    $finder->addPath( 'tests/' );
    $finder->load();
    $classes = $finder->getSchemas();

    foreach( $finder as $class ) {
        $class; // schema class.
    }


