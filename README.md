LazyRecord
==========

Command-line Usage
------------------

To build config:

    lazy build-conf path/to/config.yml

To generate SQL schema:

    lazy build-schema path/to/AuthorSchema.php

To import SQL schema into database:

    lazy build-sql path/to/AuthorSchema.php

    lazy build-sql path/to/schema/

LazyRecord will generate schema in pure-php array, in-place

    path/schema/AuthorSchemaProxy.php
    path/schema/AuthorBase.php           // auto-generated AuthoBase 
    path/schema/Author.php               // customizeable

    path/schema/AuthorCollectionBase.php // auto-generated AuthorCollection extends AuthorCollectionBase {  }
    path/schema/AuthorCollection.php     // customizable

    path/schema/foo/bar/BookBase.php
    path/schema/foo/bar/Book.php

    path/schema/foo/bar/BookCollection.php
    path/schema/foo/bar/BookSchemaProxy.php

    path/classmap.php        // application can load Model, Schema class from this mapping file
    path/datasource.php      // export datasource config

### Connecting the dots

Initialize loader:

    $lazyLoader = new ConfigLoader;
    $lazyLoader->load( 'path/to/config.php' );   // this initialize data source into connection manager.

To setup QueryDriver:
 
    $driver = QueryDriver::getInstance('data_source_id');
    $driver->configure('driver','pgsql');
    $driver->configure('quote_column',true);
    $driver->configure('quote_table',true);

To create a model record:

    $author = new Author;
    $author->create(array(
        'name' => 'Foo'
    ));

    Author::load( ... );

To create a collection object:

    $authors = new Collection;
    $authors->where()
        ->equal( 'id' , 'foo' );

To get PDO connection:

    $pdo = \LazyRecord\ConnectionManager::getInstance()->getConnection('default');



