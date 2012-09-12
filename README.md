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
bootstrap:
  - db/bootstrap.php
schema:
  paths:
    - src/
data_sources:
  default:
    dsn: 'sqlite:test'
```

Then write your bootstrap script `db/bootstrap.php`, which is a simple SPL classloader:

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

Next, write your model schema file:

```sh
$ vim src/YourApp/Model/UserSchema.php
```

Put the content into your file:

```php
<?php
namespace YourApp\Model;
use LazyRecord\Schema\SchemaDeclare;

class UserSchema extends SchemaDeclare 
{
    public function schema()
    {
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

Now you can write your application code,
But first you need to write your lazyrecord config loader code:

```
$ vim app.php
```

```php
<?php
require 'db/bootstrap.php';
$config = new LazyRecord\ConfigLoader;
$config->load('.lazy.yml');
$config->init();
```

The `init` method initializes data sources to ConnectionManager, but it won't
create connection unless you need to operate your models.

Append your application code to the end of `app.php` file:

```php
<?php
$user = new YourApp\Model\User;
$ret = $user->create(array('account' => 'guest', 'password' => '123123' ));
if( ! $ret->success ) {
    echo $ret;
}
```

Please check `doc/` directory for more details.

## Migration Support

If you need to modify schema code, like adding new columns to a table, you 
can use the amazing migration feature to migrate your database to the latest version.

Once you modified the schema code, you can execute `lazy diff` command to compare
current exisiting database table:

    $ lazy diff
    + table 'authors'            tests/schema/tests/Author.php
    + table 'addresses'          tests/schema/tests/Address.php
    + table 'author_books'       tests/schema/tests/AuthorBook.php
    + table 'books'              tests/schema/tests/Book.php
    + table 'users'              tests/schema/tests/User.php
    + table 'publishers'         tests/schema/tests/Publisher.php
    + table 'Edm'                tests/schema/tests/Edm.php
    + table 'names'              tests/schema/tests/Name.php
    + table 'i_d_numbers'        tests/schema/tests/IDNumber.php
    + table 'wines'              tests/schema/tests/Wine.php

which shows the diff from database. and now you can generate the migration script 
or upgrade database schema directly.

to upgrade database schema directly, you can simply run:

    $ lazy migrate -U

to upgrade database schema through a customizable migration script, you can 
generate a new migration script like:

    $ lazy migrate --diff AddUserRoleColumn
    Loading schema objects...
    Creating migration script from diff
    Found 10 schemas to compare.
        Found schema 'tests\AuthorSchema' to be imported to 'authors'
        Found schema 'tests\AddressSchema' to be imported to 'addresses'
        Found schema 'tests\AuthorBookSchema' to be imported to 'author_books'
        Found schema 'tests\BookSchema' to be imported to 'books'
        Found schema 'tests\UserSchema' to be imported to 'users'
        Found schema 'tests\PublisherSchema' to be imported to 'publishers'
        Found schema 'tests\EdmSchema' to be imported to 'Edm'
        Found schema 'tests\NameSchema' to be imported to 'names'
        Found schema 'tests\IDNumber' to be imported to 'i_d_numbers'
        Found schema 'tests\Wine' to be imported to 'wines'
    Migration script is generated: db/migrations/20120912_AddUserRoleColumn.php

now you can edit your migration script, which is auto-generated:

    vim db/migrations/20120912_AddUserRoleColumn.php

the migration script looks like:

```php
<?php

class AddUserColumn_1347451491  extends \LazyRecord\Migration\Migration {

    public function upgrade() { 
        $this->importSchema(new tests\AuthorSchema);
        $this->importSchema(new tests\AddressSchema);
        $this->importSchema(new tests\AuthorBookSchema);
        $this->importSchema(new tests\BookSchema);
        $this->importSchema(new tests\UserSchema);
        $this->importSchema(new tests\PublisherSchema);
        $this->importSchema(new tests\EdmSchema);
        $this->importSchema(new tests\NameSchema);
        $this->importSchema(new tests\IDNumber);
        $this->importSchema(new tests\Wine);
        
    }

    public function downgrade() { 
        $this->dropTable('authors');
        $this->dropTable('addresses');
        $this->dropTable('author_books');
        $this->dropTable('books');
        $this->dropTable('users');
        $this->dropTable('publishers');
        $this->dropTable('Edm');
        $this->dropTable('names');
        $this->dropTable('i_d_numbers');
        $this->dropTable('wines');
        
    }
}
```

The built-in migration generator not only generates the upgrade script,
but also generates the downgrade script, you can modify it to anything as you
want.

After the migration script is generated, you can check the status of 
current database and waiting migration scripts:

    $ lazy migrate --status
    Found 1 migration script to be executed.
    - AddUserColumn_1347451491

now you can run upgrade command to 
upgrade database schema through the migration script:

    $ lazy migrate --up

If you regret, you can run downgrade migrations through the command:

    $ lazy migrate --down

But please note that SQLite doesn't support column renaming and column dropping.

To see what migration script could do, please check the documentation of SQLBuilder package.

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

