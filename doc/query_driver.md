QueryDriver
===========
QueryDriver saves db driver informations like quote handler, escape handler,
feature checking, table quotes, column quotes ... etc.

QueryDriver is inherited from SQLBuilder\Driver class.

    $driver = LazyRecord\QueryDriver::getInstance( 'data_source_id' );

Free all instances

    LazyRecord\QueryDriver::getInstance()->free();

The original `sqlbuilder\driver` usage:

    $driver = new SQLBuilder\Driver;
    $driver->configure('driver','pgsql');

trim spaces

    $driver->configure('trim',true);

use named parameter

    $driver->configure('placeholder','named');

string quote handler

    $driver->configure('quoter',array($pdo,'quote'));

custom quoter

    $driver->quoter = function($string) { 
        return your_escape_function( $string );
    };


