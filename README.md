LazyRecord
==========

LazyRecord is an open-source Object-Relational Mapping (ORM) for PHP5. 

It allows you to access your database very easily by using ActiveRecord
pattern API.

LazyRecord uses code generator to generate static code, which reduces runtime 
costs, therefore it's pretty lightweight and fast. 

LazyRecord is not like PropelORM, it doesn't use ugly XML as its schema or
config file, LazyRecord uses simpler YAML format config file and it compiles
YAML to pure PHP code to improve the performance of config loading.

LazyRecord also has a simpler schema design, you can define your model schema 
very easily and you can even embed closure in your schema classes.

<div style="width:425px" id="__ss_12638921"> <strong style="display:block;margin:12px 0 4px"><a href="http://www.slideshare.net/c9s/lazyrecord-the-fast-orm-for-php" title="LazyRecord: The Fast ORM for PHP" target="_blank">LazyRecord: The Fast ORM for PHP</a></strong> <iframe src="http://www.slideshare.net/slideshow/embed_code/12638921" width="425" height="355" frameborder="0" marginwidth="0" marginheight="0" scrolling="no"></iframe> <div style="padding:5px 0 12px"> View more <a href="http://www.slideshare.net/" target="_blank">presentations</a> from <a href="http://www.slideshare.net/c9s" target="_blank">Yo-An Lin</a> </div> </div>


Concept
--------

- Function calls in PHP are very slow, so we bulid the model schema data
  statically, convert all definitions (default value, validator, filter, valid
  value builder) into classes and static PHP array, this keeps these model
  classes very lightweight and fast.
  
- In the runtime, all the same model objects use the same 
  schema object, and we can reuse the prebuild data from the static schema class.

- We keep base model class constructor empty, so when you are querying data from
  database, these model objects can be created very quickly without extra
  costs.


Feature
-------

* Fast
* Simple, Lightweight Pure PHP Model Schema (No XML)
* PDO, MySQL, Pgsql, SQLite support.
* Multiple data source support.
* Mix-in model support.
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

Install from composer:


```json
{
    "require": {
        "corneltek/lazyrecord": "1.9.*"
    }
}
```

Download the lazyrecord command-line binary:


    $ curl -O http://raw.github.com/c9s/LazyRecord/master/lazy
    $ chmod +x lazy
    $ mv lazy /usr/bin


Getting Started
---------------

### Configuring Database

Change directory to your project, run `init` command to initialize 
your database settings.

```sh
$ mkdir myapp
$ cd myapp
$ lazy init 
Database driver [sqlite] [sqlite/pgsql/mysql/] sqlite
Database name [:memory:] test
```

Then edit your config file:

```sh
$ vim db/config/database.yml
```

Suppose your application code is located in `src/` directory, 
then you should provide your schema path in following format:

```yaml
---
bootstrap:
  - vendor/autoload.php   # load the classloader from composer.
schema:
  auto_id: 1
  paths:
    - src/    # where you store the schema class files.
data_sources:
  default:
    dsn: 'sqlite:test'
```

In the above config file, the `auto_id` means an id column with auto-increment
integer primary key is automatically inserted into every schema class, so you
don't need to declare an primary key id column in your every schema file.


### Writing Model Schema

Next, write your model schema file:

```sh
$ vim src/YourApp/Model/UserSchema.php
```

Put the content into your file:

```php
namespace YourApp\Model;
use LazyRecord\Schema;

class UserSchema extends Schema
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

### Building Static Schema Files

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


### Creating Database

If you are using postgresql or mysql, you can create your database with
`create-db` command:

```sh
$ lazy create-db
```


### Building SQL From Model Schemas

Now you need to build SQL schema into your database, simply run `build-sql`,
`-d` is for debug mode, which prints all generated SQL statements:

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

### Writing Application Code

Now you can write your application code,
But first you need to write your lazyrecord config loader code:

```
$ vim app.php
```

```php
require 'vendor/autoload.php';
$config = new LazyRecord\ConfigLoader;
$config->load( __DIR__ . '/db/config/database.yml');
$config->init();
```

The `init` method initializes data sources to ConnectionManager, but it won't
create connection unless you need to operate your models.


### Sample Code Of Operating The User Model Object

Now append your application code to the end of `app.php` file:

```php
$user = new YourApp\Model\User;
$ret = $user->create(array('account' => 'guest', 'password' => '123123' ));
if( ! $ret->success ) {
    echo $ret->message;  // get the error message
    if ($ret->exception) {
        echo $ret->exception;  // get the exception object
    }
    echo $ret; // __toString() is supported
}
```

Please check `doc/` directory for more details.



Basic Usage
-----------

### Model Accessor

LazyRecord's BaseModel class provides a simple way to retrieve result data from the `__get` magic method,
by using the magic method, you can retrieve the column value and objects from relationship.

```php
$record = new MyApp\Model\User( 1 );   // select * from users where id = 1;
$record->name;    // get "users.name" and inflate it.
```

The `__get` method is dispatching to `get` method, if you don't want to use the magic method,

```php
$record->get('name');
```


The magic method calls value inflator, which can help you inflate values like
DateTime objects, it might be slower, if you want performance, you can simply
do:

```php
$record->getValue('name');
```

BaseModel also supports iterating, so you can iterate the data values with foreach:

```php
foreach( $record as $column => $rawValue ) {

}
```





### Model Operation

To create a model record:

```php
$author = new Author;
$ret = $author->create(array(
    'name' => 'Foo'
));
if ( $ret->success ) {
    echo 'created';
}
```

To find record:
    
```php
$ret = $author->find(123);
$ret = $author->find(array( 'foo' => 'Name' ));
if ( $ret->success ) {

} else {
    // handle $ret->exception or $ret->message
}
```

To find record with (static):

```php
$record = Author::load(array( 'name' => 'Foo' ));
```

To find record with primary key:

```php
$record = Author::load( 1 );
```

To update record:

```php
$author->update(array(  
    'name' => 'Author',
));
```

To update record (static):

```php
$ret = Author::update( array( 'name' => 'Author' ) )
    ->where()
        ->equal('id',3)
        ->execute();

if( $ret->success ) {
    echo $ret->message;
}
else {
    // pretty print error message, exception and validation errors for console
    echo $ret;

    $e = $ret->exception; // get exception
    $validations = $ret->validations; // get validation results
}
```


### Collection

To create a collection object:

```php
$authors = new AuthorCollection;
```

To make a query (the Query syntax is powered by SQLBuilder):

```php
$authors->where()
    ->equal( 'id' , 'foo' )
    ->like( 'content' , '%foo%' )
    ;
```

Or you can do:

```php
$authors->where(array( 
    'name' => 'foo'
));
```


#### Iterating a Collection

```php
$authors = new AuthorCollection;
foreach( $authors as $author ) {
    echo $author->name;
}
```

Model Schema
------------

### Defining Schema Class

Simply extend class from `LazyRecord\Schema`, and define your model columns 
in the `schema` method, e.g.,

```php
<?php
namespace tests;
use LazyRecord\Schema;

class BookSchema extends Schema
{

    function schema()
    {
        $this->column('title')
            ->unique()
            ->varchar(128);

        $this->column('subtitle')
            ->varchar(256);

        $this->column('isbn')
            ->varchar(128)
            ->immutable()
            ;

        $this->column('description')
            ->text();

        $this->column('view')
            ->default(0)
            ->integer();

        $this->column('publisher_id')
            ->isa('int')
            ->integer();

        $this->column('published_at')
            ->isa('DateTime')
            ->timestamp();

        $this->column('created_by')
            ->integer()
            ->refer('tests\UserSchema');

        $this->belongsTo('created_by', 'tests\UserSchema','id', 'created_by');

        /** 
         * column: author => Author class 
         *
         * $book->publisher->name;
         *
         **/
        $this->belongsTo('publisher','\tests\PublisherSchema', 'id', 'publisher_id');

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', '\tests\AuthorBookSchema', 'book_id', 'id');


        /**
         * get BookAuthor.author 
         */
        $this->manyToMany( 'authors', 'book_authors', 'author' )
            ->filter(function($collection) { return $collection; });
    }
}
```

### Defining Column Types

```php
$this->column('foo')->integer();
$this->column('foo')->float();
$this->column('foo')->varchar(24);
$this->column('foo')->text();
$this->column('foo')->binary();
```

Text:

```php
$this->column('name')->text();
```

Boolean:

```php
$this->column('name') ->boolean();
```

Integer:

```php
$this->column('name')->integer();
```

Timestamp:

```php
$this->column('name')->timestamp();
```

Datetime:

```php
$this->column('name')->datetime();
```



### Using Validator

Write your validator as a closure, here comes the simplest sample code:

```php
$this->column('name')
    ->varchar(128)
    ->validator(function($val) {
        if ( in_array($val,'foo','bar')) {
            return array(true, "OK");
        }
        return array(false,"Not a valid value.");
    });
```

If you need, you may also get the arguments and the current record object:

```php
$this->column('name')
    ->varchar(128)
    ->validator(function($val, $args, $record) {
        if ( in_array($val,'foo','bar')) {
            return array(true, "OK");
        }
        return array(false,"Not a valid value.");
    });
```

If you are using ValidationKit, we can pass the validator class name:

```php
$this->column('id_number')
    ->varchar(10)
    ->validator('TW\\IDNumberValidator');
```

### Defining Inflator And Deflator

Inflator and Deflator are used in some situation like 
"read a timestamp and construct a DateTime object with the raw value", 
Or "convert a DateTime object into a string that is acceptable for database"

LazyRecord provides some built-in inflators and deflators, e.g., timestamp columns:

```php
$this->column('created_on')
    ->timestamp();
```

So that when you are retrieving "created\_on" from record object, you get a `DateTime` object.

You can also define your own deflator or inflator, for example:

```php
$this->column('serialized_content')
    ->varchar(128)
    ->inflator(function($val) {
        return unserialize($val);
    })
    ->deflator(function($val) {
        return serialize($val);
    });
```

The above code does unserialize/serialize automatically when you're 
trying to update/create the record object.

### Defining Default Value

```php
$this->column('foo')
    ->default('Default')
    ->default( array('current_timestamp') ) // raw sql string
    ->default(function() { 
            return date('c');
    });
```

### Defining Required Column

```php
$this->column('email')
    ->required()
    ->varchar(128);
```

### Defining ValidValues

```php
$this->column('role')
    ->required()
    ->validValues(array('user','admin','guest'));
```

Defining ValidValues with label

```php
$this->column('bar')
    ->required()
    ->validValues( array( 'label' => 'value'  ) );
```

### Defining Filter

```php
$this->column('content')
    ->required()
    ->filter( function($val) {  
        return preg_replace('#word#','zz',$val);  
    });
```

### Using Mixin Schemas

Simply define mixin in your model schema method:

```php
$this->mixin('tests\\MetadataMixinSchema');
```

The Mixin Schema Class, e.g., MetadataSchema:

```php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinSchemaDeclare;

class MetadataSchema extends MixinSchemaDeclare
{
    public function schema()
    {
        $this->column('updated_on')
            ->isa('DateTime')
            ->default(function() { 
                return date('c'); 
            })
            ->timestamp();

        $this->column('created_on')
            ->isa('DateTime')
            ->default(function() { 
                return date('c'); 
            })
            ->timestamp();
    }
}
```

#### Defining Mixin Method

```php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinSchemaDeclare;

class MetadataSchema extends MixinSchemaDeclare
{
    public function schema()
    {
        // ... define your schema here
    }

    public function fooMethod($record, $arg1, $arg2, $arg3, $arg4)
    {
        // ...
        return ...;
    }
}
```

Then you can use the `fooMethod` on your model object:

```php
$record = new FooModal;
$result = $record->fooMethod(1,2,3,4);
```



### Defining Model Relationship

#### Belongs to

`belongsTo(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name = 'id')`

```php
$this->belongsTo( 'author' , '\tests\AuthorSchema', 'id' , 'author_id' );
$this->belongsTo( 'address' , '\tests\AddressSchema', 'address_id' );
```

#### Has One

`one(accessor_name, self_column_name, foreign_schema_class_name, foreign_schema_column_name)`

```php
$this->one( 'author', 'author_id', '\tests\AuthorSchema' , 'id' );
```

#### Has Many

`many(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name )`

```php
$this->many( 'addresses', '\tests\AddressSchema', 'author_id', 'id');
$this->many( 'author_books', '\tests\AuthorBookSchema', 'author_id', 'id');
```

To define many to many relationship:

```php
$this->manyToMany( 'books', 'author_books' , 'book' );
```


Usage:

```php
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


### Do Some Preparation When Model Is Ready

If you want to do something after the schmea is created into a database, you can define a
`bootstrap` method in your schema class:

```php
namespace User;
class UserSchema extends LazyRecord\Schema { 
    public function schema() {
        // ...
    }
    public function bootstrap($model) {
        // do something you want
    }
}
```

The bootstrap method is triggerd when you run:

`lazy build-sql`

### Using Multiple Data Source

You can define specific data source for different model in the model schema:

```php
use LazyRecord\Schema;
class UserSchema extends Schema {
    public function schema() {
        $this->writeTo('master');
        $this->readFrom('slave');
    }
}
```

Or you can specify for both (read and write):

```php
use LazyRecord\Schema;
class UserSchema extends Schema {
    public function schema() {
        $this->using('master');
    }
}
```

### Defining BaseData Seed

The basedata seed script is executed after you run `build-sql`, which means
all of your tables are ready in the database.

To define a basedata seed script:

```php
namespace User;
class Seed { 
    public static function seed() {

    }
}
```

Then update your config file by adding the class name of the data
seed class:

```yaml
seeds:
  - User\Seed
  - System\Seed
  - System\TestingSeed
```

Migration
---------

If you need to modify schema code, like adding new columns to a table, you 
can use the amazing migration feature to migrate your database to the latest
change without pain.

Once you modified the schema code, you can execute `lazy diff` command to compare
current exisiting database table:

    $ lazy diff
    + table 'authors'            tests/schema/tests/Author.php
    + table 'addresses'          tests/schema/tests/Address.php
    + table 'author_books'       tests/schema/tests/AuthorBook.php
    + table 'books'              tests/schema/tests/Book.php
    + table 'users'              tests/schema/tests/User.php
    + table 'publishers'         tests/schema/tests/Publisher.php
    + table 'names'              tests/schema/tests/Name.php
    + table 'wines'              tests/schema/tests/Wine.php

As you can see, we added a lot of new tables (schemas), and LazyRecord parses
the database tables to show you the difference to let you know current
status.

> Currently LazyRecord supports SQLite, PostgreSQL, MySQL table parsing.

now you can generate the migration script or upgrade database schema directly.

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
        Found schema 'tests\NameSchema' to be imported to 'names'
        Found schema 'tests\Wine' to be imported to 'wines'
    Migration script is generated: db/migrations/20120912_AddUserRoleColumn.php

now you can edit your migration script, which is auto-generated:

    vim db/migrations/20120912_AddUserRoleColumn.php

the migration script looks like:

```php
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

## Mix-In Schema

...

## Basedata Seed


## Setting up QueryDriver for SQL syntax
 
```php
$driver = LazyRecord\QueryDriver::getInstance('data_source_id');
$driver->configure('driver','pgsql');
$driver->configure('quote_column',true);
$driver->configure('quote_table',true);
```

## A More Advanced Model Schema

```php
use LazyRecord\Schema;

class AuthorSchema extends Schema
{
    function schema()
    {
        $this->column('id')
            ->integer()
            ->primary()
            ->autoIncrement();

        $this->column('name')
            ->varchar(128)
            ->validator(function($val) { .... })
            ->filter( function($val) {  
                        return preg_replace('#word#','zz',$val);  
            })
            ->inflator(function($val) {
                return unserialize($val);
            })
            ->deflator(function($val) {
                return serialize($val);
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

        $this->seeds('User\\Seed')
    }
}
```

Documentation
=============

For the detailed content,  please take a look at the `doc/` directory.


Contribution
============

Everybody can contribute to LazyRecord. You can just fork it, and send Pull
Requests. 

You have to follow PSR Coding Standards and provides unit tests
as much as possible.


Hacking
=======


Setting Up Environment
----------------------

Use Composer to install the dependency:

    composer install --dev

To deploy a testing environment, you need to install dependent packages.

Run script and make sure everything is fine:

    php bin/lazy

Database configuration is written in `phpunit.xml` file, the 
following steps are based on the default configuration. you may also take a look at `.travis.yml` for example.

### Unit Testing with MySQL database

To test with mysql database:

    $ mysql -uroot -p
    > create database testing charset utf8;
    > grant all privileges on testing.* to 'testing'@'localhost' identified by 'testing';

### Unit Testing with PostgreSQL database

To test with pgsql database:

    $ sudo -u postgres createuser --no-createrole --no-superuser --pwprompt testing
    $ sudo -u postgres createdb -E=utf8 --owner=testing testing

### Run PHPUnit

    $ phpunit

### Command-line testing

To test sql builder from command-line, please copy the default testing config

    $ cp db/config/database.testing.yml db/config/database.yml

Build config

    $ php bin/lazy build-conf db/config/database.yml

Build Schema files

    $ php bin/lazy build-schema

We've already defined 3 data sources, they were named as 'mysql', 'pgsql', 'sqlite' , 
now you can insert schema sqls into these data sources:

    $ php bin/lazy build-sql --rebuild -D=mysql
    $ php bin/lazy build-sql --rebuild -D=pgsql
    $ php bin/lazy build-sql --rebuild -D=sqlite


PROFILING
==============


    $ scripts/run-xhprof

OR

    $ phpunit -c phpunit-xhprof.xml
    $ cd xhprof_html
    $ php -S localhost:8888


LICENSE
===============
BSD License



