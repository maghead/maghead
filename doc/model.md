Model
=====

## Create

<?php
    $author = new Author;
    $ret = $author->create(array(
        'name' => 'Foo'
    ));
?>

`$ret` is an operation result, you can check if this operation succeed.

To check status:

<?php
    if( true === $ret->success ) {
        echo $ret->message;   // record created
    }
    else {
        echo $ret->exception;  // exception
        echo $ret->sql;   // sql statement
        print_r( $ret->vars );   // variables that applied for PDO
    }
?>

## Load

Load record with primary key through constructor:

<?php
    $author = new Author( 1 );
?>

Load record with hash through constructor:

<?php
    $author = new Author( array( 
        'name' => 'John'
    ));
?>

use load method to load record:

<?php
    $author->load( array(  
        'name' => 'John',
    ));
?>

## Update

<?php
    $author->update(array(
        'name' => 'Mary'
    ));
?>

## Delete

<?php
    $author->load(1);
    $author->delete();
?>


# Static Helper Functions

## Create

<?php
    $record = \TestApp\Author::create(array( 
        'name' => 'Mary',
        'email' => 'zz@zz',
        'identity' => 'zz',
    ));
?>

## Load

<?php
    $record = \TestApp\Author::load( 1 );
    $record = \TestApp\Author::load( array( 'id' => $id ));
?>

## Update

The following lines runs:

    UPDATE authors SET name = 'Rename' WHERE name = 'Mary'

<?php

    $ret = \TestApp\Author::update(array( 'name' => 'Rename' ))
        ->where()
        ->equal('name','Mary')
        ->execute();
?>

## Delete

<?php

    $ret = \TestApp\Author::delete()
        ->where()
        ->equal('name','Rename')
        ->execute();

    $ret = \TestApp\Author::delete()->execute();

?>

## Connections

    $pdo = $model->getReadConnection(); // PDO adapter
    $pdo = $model->getWriteConnection(); // PDO adapter

    $pdo = $model->getConnection( 'master' );

## Query

### load data from query

    $model->loadQuery( 'master', 'select * from ... where id = :id' , array( ':id' => $id ));

    $model->dbPrepareAndExecute( {pdo} , {sql}, {args} );

