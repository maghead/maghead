QueryDriver
===========
QueryDriver saves db driver informations like quote handler, escape handler,
feature checking, table quotes, column quotes ... etc.

QueryDriver is inherited from SQLBuilder\Driver class.

    $driver = Maghead\QueryDriver::getInstance( 'data_source_id' );

Free all instances

    Maghead\QueryDriver::getInstance()->free();

## The original `sqlbuilder\driver` usage

To create a sql driver for pgsql:

    $driver = new SQLBuilder\Driver('pgsql');

or configure driver type by method:

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

    $driver->type; // get driver type
    $driver->quoter; // get quoter callback
    $driver->inflator; // get inflator callback


