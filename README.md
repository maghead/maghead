LazyRecord
==========

Synopsis
--------


```php
<?
    $author = new Author;
    $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

    // has many
    $address = $author->addresses->create(array( 
        'address' => 'farfaraway'
    ));

    $address->delete();

    // create related address
    $author->addresses[] = array( 'address' => 'Harvard' );

    $addresses = $author->addresses->items();
    is( 'Harvard' , $addresses[0]->address );

    foreach( $author->addresses as $address ) {
        echo $address->address , "\n";
    }
```


Requirement
-----------
- PHP 5.3.0 (MIN) 




Command-line Usage
------------------
Create a config skeleton:

    $ lazy init-conf

Then build config:

    $ lazy build-conf path/to/config.yml

Define your model schema, note: the schema file name must be with suffix "Schema".

    $ vim src/App/Model/AuthorSchema.php

    use LazyRecord\Schema\SchemaDeclare;

    class AuthorSchema extends SchemaDeclare
    {
        function schema()
        {
            $this->column('id')
                ->type('integer')
                ->isa('int')
                ->primary()
                ->autoIncrement();

            $this->column('name')
                ->isa('str')
                ->varchar(128);

            $this->column('email')
                ->isa('str')
                ->required()
                ->varchar(128);

            $this->column('confirmed')
                ->isa('bool')
                ->default(false)
                ->boolean();
        }
    }

To generate SQL schema:

    lazy build-schema path/to/AuthorSchema.php

Then you should have these files:

    src/App/Model/AuthorSchema.php
    src/App/Model/AuthorBase.php
    src/App/Model/Author.php
    src/App/Model/AuthorBaseCollection.php
    src/App/Model/AuthorCollection.php

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
 
    $driver = LazyRecord\QueryDriver::getInstance('data_source_id');
    $driver->configure('driver','pgsql');
    $driver->configure('quote_column',true);
    $driver->configure('quote_table',true);

To create a model record:

    $author = new Author;
    $author->create(array(
        'name' => 'Foo'
    ));

Find record:
    
    $author->find(123);
    $author->find(array( 'foo' => 'Name' ));

Find record with (static):

    $record = Author::load(array( 'name' => 'Foo' ));

Find record with primary key:

    $record = Author::load( 1 );

Update record:

    $author->update(array(  
        'name' => 'Author',
    ));

Update record (static):

    $ret = Author::update( array( 'name' => 'Author' ) )
        ->where()
            ->equal('id',3)
            ->execute();

    if( $ret->success ) {
        echo $ret->message;
    }
    else {
        echo $ret->exception->getMessage();
    }

To create a collection object:

    $authors = new Collection;
    $authors->where()
        ->equal( 'id' , 'foo' );

    $authors = new AuthorCollection;
    $items = $authors->items();
    foreach( $items as $item ) {
        echo $item->id;
    }

    $authors = new AuthorCollection;
    foreach( $authors as $author ) {
        echo $author->name;
    }


To get PDO connection:

    $pdo = \LazyRecord\ConnectionManager::getInstance()->getConnection('default');



Setup Testing Environment
-------------------------
copy the default config:

    $ cp config/database.yml.testing config/database.yml

Modify database driver, user, pass:

    $ vim config/database.yml

Build php config:

    $ lazy build-conf config/database.yml

Build schema files (if you added new schema files)

    $ lazy build-schema

Build sql (inject sql into database)

    $ lazy build-sql

Run tests:

    $ phpunit tests

To run unit tests with pgsql DSN:

    createdb lazy_test
    DB_DSN="pgsql:dbname=lazy_test;" phpunit tests

To run unit tests with mysql DSN:

	DB_DSN="mysql:dbname=lazy_test" DB_USER=root DB_PASS=123123 phpunit tests

For pgsql:

    createuser {user}
    createdb {database}

