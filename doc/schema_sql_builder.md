
# Schema SQL Builder

To build table sql from schema, you need a query driver object:

    use LazyRecord\Schema\SqlBuilderFactory;

    $connectionManager = \LazyRecord\ConnectionManager::getInstance();
    $driver = $connectionManager->getQueryDriver($id);

Then use SqlBuilderFactory to create a Sql builder object:

    $builder = SqlBuilderFactory::create($driver);

    $builder = SqlBuilderFactory::create($driver, array( 
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

