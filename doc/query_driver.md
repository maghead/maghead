QueryDriver
===========
QueryDriver is used for generating sql statements, like insert, update, delete .. etc.

QueryDriver is inherited from SQLBuilder\Driver class.

    $driver = LazyRecord\QueryDriver::getInstance( 'data_source_id' );

Free all instances

    LazyRecord\QueryDriver::getInstance()->free();
