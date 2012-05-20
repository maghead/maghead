LazyRecord
==========

<div style="width:425px" id="__ss_12638921"> <strong style="display:block;margin:12px 0 4px"><a href="http://www.slideshare.net/c9s/lazyrecord-the-fast-orm-for-php" title="LazyRecord: The Fast ORM for PHP" target="_blank">LazyRecord: The Fast ORM for PHP</a></strong> <iframe src="http://www.slideshare.net/slideshow/embed_code/12638921" width="425" height="355" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe> <div style="padding:5px 0 12px"> View more <a href="http://www.slideshare.net/" target="_blank">presentations</a> from <a href="http://www.slideshare.net/c9s" target="_blank">Yo-An Lin</a> </div> </div>

Features
--------

* PDO.
* MySQL, Pgsql, SQLite support.
* Multiple Data source
* Schema/Database diff
* Fast.

Synopsis
--------

```sh
    $ lazy build-conf

    $ lazy build-schema

    $ lazy build-sql --data-source=mysql

    $ lazy diff

    $ lazy diff --data-source=mysql

    $ lazy diff --data-source=pgsql
```


```php
<?
    $author = new Author;
    $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

    // PHP5.4
    $author->create([ 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ]);

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

Please check `doc/` directory for more details.


Requirement
-----------
- PHP 5.3.0 (MIN) 
- YAML extension
- PDO
- PDO-mysql (optional)
- PDO-pgsql (optional)
- PDO-sqlite (optional)

Install from Installer
----------------------

Install LazyRecord to system:

    sudo bash -c "$(curl -s -L https://raw.github.com/c9s/LazyRecord/master/install.sh)"

If you're using phpbrew:

    bash -c "$(curl -s -L https://raw.github.com/c9s/LazyRecord/master/install.sh)"



Install manually
----------------
Install required extensions:

    sudo pecl install apc
    sudo pecl install yaml

get php source code and install these extensions:

* `pdo_mysql` (optional)
* `pdo_pgsql` (optional)
* `pdo_sqlite` (optional)

To build a PHP for LazyRecord, you can use phpbrew:

    phpbrew -d install --no-test php-5.4.1 +mysql +pdo +pgsql +sqlite+pcntl+posix+ipc+readline+openssl

Or to build PHP with custom postgresql base dir:

    phpbrew -d install --no-test php-5.4.1 +pdo +mysql +pgsql=/opt/local/lib/postgresql91 +sqlite+pcntl+posix+ipc+readline+openssl

And that will be:

    ./configure '--prefix=/Users/c9s/.phpbrew/php/php-5.4.1'
    '--with-config-file-path=/Users/c9s/.phpbrew/php/php-5.4.1/etc'
    '--with-config-file-scan-dir=/Users/c9s/.phpbrew/php/php-5.4.1/var/db'
    '--with-pear=/Users/c9s/.phpbrew/php/php-5.4.1/lib/php' '--disable-all'
    '--enable-bcmath' '--enable-ctype' '--enable-dom' '--enable-exif'
    '--enable-fileinfo' '--enable-filter' '--enable-hash' '--enable-json'
    '--enable-libxml' '--enable-mbregex' '--enable-mbstring' '--enable-phar'
    '--enable-session' '--enable-short-tags' '--enable-simplexml'
    '--enable-sockets' '--enable-tokenizer' '--enable-xml' '--enable-xmlreader'
    '--enable-xmlwriter' '--enable-zip' '--with-xsl' '--with-tidy'
    '--with-xmlrpc' '--with-mhash' '--with-pcre-regex' '--with-zlib=/usr'
    '--with-libxml-dir=/opt/local' '--with-curl=/opt/local'
    '--with-gettext=/opt/local' '--enable-pdo' '--with-bz2=/usr' '--enable-cli'
    '--enable-fpm' '--enable-posix' '--enable-calendar' '--enable-sockets'
    '--with-readline=/usr' '--with-libedit=/usr' '--with-mysql=mysqlnd'
    '--with-mysqli=mysqlnd' '--with-pdo-mysql=mysqlnd' '--with-pgsql'
    '--with-pdo-pgsql' '--with-sqlite3' '--with-pdo-sqlite' '--enable-pcntl'
    '--enable-shmop' '--enable-sysvsem' '--enable-sysvshm' '--enable-sysvmsg'
    '--with-openssl=/opt/local'

And update your php.ini:

    date.timezone = Asia/Taipei
    phar.readonly = Off

Install LazyRecord:

    git clone git://github.com/c9s/LazyRecord.git
    cd LazyRecord
    sudo pear channel-discover pear.corneltek.com
    sudo pear channel-discover pear.twig-project.org
    sudo pear install -f package.xml


Command-line Usage
------------------
Create a config skeleton:

    $ lazy init-conf

Then build config:

    $ lazy build-conf path/to/config.yml

Define your model schema:

    $ vim src/App/Model/AuthorSchema.php

```php
<?php
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
                ->varchar(128)
                ->validator(function($val) { .... })
                ->filter( function($val) {  
                            return preg_replace('#word#','zz',$val);  
                })
                ->validValues( 1,2,3,4,5 )
                ->defaultBuilder(function() { 
                    return date('c');
                })
                ;

            $this->column('email')
                ->required()
                ->varchar(128);

            $this->column('confirmed')
                ->default(false)
                ->boolean();
        }
    }
```

To generate SQL schema:

    lazy build-schema path/to/AuthorSchema.php

Then you should have these files:

    src/App/Model/AuthorSchema.php
    src/App/Model/AuthorBase.php
    src/App/Model/Author.php
    src/App/Model/AuthorBaseCollection.php
    src/App/Model/AuthorCollection.php

Now edit your src/App/Model/Author.php file to extend.


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


```php
<?php
    $lazyLoader = new ConfigLoader;
    $lazyLoader->load( 'path/to/config.php' );   // this initialize data source into connection manager.
    $lazyLoader->init();
```

To setup QueryDriver:
 
```php
<?php
    $driver = LazyRecord\QueryDriver::getInstance('data_source_id');
    $driver->configure('driver','pgsql');
    $driver->configure('quote_column',true);
    $driver->configure('quote_table',true);
?>
```

To create a model record:

```php
<?php
    $author = new Author;
    $author->create(array(
        'name' => 'Foo'
    ));
```

To find record:
    
```php
<?php
    $author->find(123);
    $author->find(array( 'foo' => 'Name' ));
```

To find record with (static):

```php
<?php
    $record = Author::load(array( 'name' => 'Foo' ));
```

To find record with primary key:

```php
<?php
    $record = Author::load( 1 );
?>
```

To update record:

```php
<?php
    $author->update(array(  
        'name' => 'Author',
    ));
```

To update record (static):

```php
<?php
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
```

To create a collection object:

```php
<?php
    $authors = new AuthorCollection;
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
?>
```

