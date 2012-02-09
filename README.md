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

    build/schema/AuthorSchemaProxy.php
    build/schema/AuthorBase.php           // auto-generated AuthoBase 
    build/schema/Author.php               // customizeable

    build/schema/AuthorCollectionBase.php // auto-generated AuthorCollection extends AuthorCollectionBase {  }
    build/schema/AuthorCollection.php     // customizable

    build/schema/foo/bar/BookBase.php
    build/schema/foo/bar/Book.php

    build/schema/foo/bar/BookCollection.php
    build/schema/foo/bar/BookSchemaProxy.php

    build/classmap.php        // application can load Model, Schema class from this mapping file
    build/datasource.php      // export datasource config

class map content:

    class => path to class

    <?php 
    return array(  
        'Author' => 'schema/build/Author.php',
        'AuthorSchemaProxy' => 'schema/build/AuthorSchema.php',
        'Ns1\Ns2\Book' => 'schema/build/AuthorSchema.php',
    );

