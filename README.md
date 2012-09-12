LazyRecord
==========

<div style="width:425px" id="__ss_12638921"> <strong style="display:block;margin:12px 0 4px"><a href="http://www.slideshare.net/c9s/lazyrecord-the-fast-orm-for-php" title="LazyRecord: The Fast ORM for PHP" target="_blank">LazyRecord: The Fast ORM for PHP</a></strong> <iframe src="http://www.slideshare.net/slideshow/embed_code/12638921" width="425" height="355" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe> <div style="padding:5px 0 12px"> View more <a href="http://www.slideshare.net/" target="_blank">presentations</a> from <a href="http://www.slideshare.net/c9s" target="_blank">Yo-An Lin</a> </div> </div>

Features
--------

* Fast
* Simple, Lightweight Pure PHP Model Schema (No XML)
* PDO, MySQL, Pgsql, SQLite support.
* Multiple Data source support.
* Migration support. upgrade, downgrade, upgrade from schema diff.
* Schema/Database diff

Requirement
-----------

PHP:

- PHP 5.3 or upper.

PHP Extensions

- yaml extension
- pdo
- mysql, pgsql or sqlite

Installation
------------

```sh
pear channel-discover pear.corneltek.com
pear channel-discover pear.twig-project.org
pear install -a -f corneltek/LazyRecord
```

Getting Started
---------------

Change directory to your project, run `init` command to initialize 
your database settings.

```sh
$ mkdir proj1
$ cd proj1
$ lazy init 
db/config
db/migration
Database driver [sqlite] [sqlite/pgsql/mysql/] sqlite
Database name [:memory:] test
Using sqlite driver
Using database test
Using DSN: sqlite:test
Creating config file skeleton...
Config file is generated: db/config/database.yml
Please run build-conf to compile php format config file.
Building config from db/config/database.yml
Making link => .lazy.yml
Done.
```

To edit your config file:

```sh
$ vim db/config/database.yml
```

Suppose your application code is located in `src/` directory, 
then you should provide your schema path in following format:

```yaml
---
schema:
  paths:
    - src/
data_sources:
  default:
    dsn: 'sqlite:test'
```

Next, write your model schema file:

```sh
$ vim src/YourApp/Model/UserSchema.php
```

Put the content into your file:

```php
<?php
namespace YourApp\Model;
use LazyRecord\Schema\SchemaDeclare;

class UserSchema extends SchemaDeclare {
    function schema() {
        $this->column('account')
            ->varchar(16);
        $this->column('password')
            ->varchar(40)
            ->filter('sha1');
    }
}
```

Then run `build-schema` command to build static schema files:

```sh
$ lazy build-schema
Finding schemas...
Found schema classes
Initializing schema generator...
    YourApp\Model\UserSchemaProxy    => src/YourApp/Model/UserSchemaProxy.php
    YourApp\Model\UserCollectionBase => src/YourApp/Model/UserCollectionBase.php
    YourApp\Model\UserCollection     => src/YourApp/Model/UserCollection.php
    YourApp\Model\UserBase           => src/YourApp/Model/UserBase.php
    YourApp\Model\User               => src/YourApp/Model/User.php
Done
```

Now you need to build SQL schema into your database, simply run `build-sql`,
`-d` is for debug mode:

```sh
$ lazy -d build-sql
Finding schema classes...
Initialize schema builder...
Building SQL for YourApp\Model\UserSchema
DROP TABLE IF EXISTS users;
CREATE TABLE users ( 
  account varchar(16),
  password varchar(40)
);

Setting migration timestamp to 1347439779
Done. 1 schema tables were generated into data source 'default'.
```

Now you can write your application code. but first, you need a SPL classloader
for library code:

```
$ vim app.php
```

```php
<?php
require 'Universal/ClassLoader/BasePathClassLoader.php';
use Universal\ClassLoader\BasePathClassLoader;
$loader = new BasePathClassLoader(array(
    dirname(__DIR__) . '/src', 
    dirname(__DIR__) . '/vendor/pear',
));
$loader->useIncludePath(true);
$loader->register();
```

Then append your lazyrecord config loader:

```php
<?php
$config = new LazyRecord\ConfigLoader;
$config->load('.lazy.yml');
$config->init();
```

The `init` method initializes data sources to ConnectionManager, but it won't
create connection unless you need to operate your models.

Append your application code to the end of `app.php` file:

```php
<?php
$user = new User;
$ret = $user->create(array('account' => 'guest', 'password' => '123123' ));
if( ! $ret->success ) {
    echo $ret;
}
```

Please check `doc/` directory for more details.


## Setting up QueryDriver for SQL syntax
 
```php
<?php
$driver = LazyRecord\QueryDriver::getInstance('data_source_id');
$driver->configure('driver','pgsql');
$driver->configure('quote_column',true);
$driver->configure('quote_table',true);
```


## Model Operations

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

## Collection

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
```

## Relationships


```php
<?php
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


## A more advanced schema code

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
                ->default(function() { 
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

