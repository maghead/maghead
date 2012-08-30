# Schema SQL Builder Redesign

Issue: <http://redmine.corneltek.com/issues/3>

    $driver = new QueryDriver;
    $builder = new Schema\SqlBuilder( $driver , array( 
        'clean' => true,
        'rebuild' => true,
    ));
    $sql = $builder->build( $schema );

    // insert query
    $pdo->query( $sql );
