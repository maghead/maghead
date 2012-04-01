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


## Update

    $author->update(array(
        'name' => 'Mary'
    ));

## Delete

    $author->load(1);
    $author->delete();


# Static Helper Functions

## Create

    $record = \tests\Author::create(array( 
        'name' => 'Mary',
        'email' => 'zz@zz',
        'identity' => 'zz',
    ));

## Load

        $record = \tests\Author::load( 1 );
        $record = \tests\Author::load( array( 'id' => $id ));

## Update

The following lines runs:

    UPDATE authors SET name = 'Rename' WHERE name = 'Mary'

    $ret = \tests\Author::update(array( 'name' => 'Rename' ))
        ->where()
        ->equal('name','Mary')
        ->execute();

## Delete

    $ret = \tests\Author::delete()
        ->where()
        ->equal('name','Rename')
        ->execute();

