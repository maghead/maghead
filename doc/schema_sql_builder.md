
# Schema SQL Builder

To build table sql from schema, you need a query driver object:

    use Maghead\Schema\SqlBuilder;

    $connectionManager = \Maghead\ConnectionManager::getInstance();
    $driver = $connectionManager->getQueryDriver($id);

Then use SqlBuilder to create a Sql builder object:

    $builder = SqlBuilder::create($driver);

    $builder = SqlBuilder::create($driver, array( 
        'rebuild' => true,  // drop table if table exists, then create.
        'clean' => true, // drop table, do not create
    ));

To build table sql from schema:

    $sqls = $builder->build($schema);

Insert into database:

    $conn = $connectionManager->getConnection($id);
    foreach($sqls as $sql) {
        $conn->query( $sql );
    }

