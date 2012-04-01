Model
=====

## Create

    $author = new Author;
    $ret = $author->create(array(
        'name' => 'Foo'
    ));

`$ret` is an operation result, you can check if this operation succeed.

To check status:

    if( true === $ret->success ) {
        echo $ret->message;   // record created
    }
    else {
        echo $ret->exception;  // exception
        echo $ret->sql;   // sql statement
        print_r( $ret->vars );   // variables that applied for PDO
    }

## Load

Load record with primary key through constructor:

    $author = new Author( 1 );

Load record with hash through constructor:

    $author = new Author( array( 
        'name' => 'John'
    ));

use load method to load record:

    $author->load( array(  
        'name' => 'John',
    ));


