SchemaGenerator
===============

Current API:

    $generator = new \LazyRecord\Schema\SchemaGenerator;
    $generator->setLogger( $logger );
    $classMap = $generator->generate( $finder->getSchemaClasses() );

New schema generator API:

    $generator = new SchemaGenerator;

    $classMap = array();
    foreach( $finder as $class ) {
        $files = $generator->generateSchemaClass($class);
    }

    $classMapes = $generator->generateSchemaClasses($classes);

