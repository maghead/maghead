Maghead
==========

[![Build Status](https://travis-ci.org/maghead/maghead.svg?branch=master)](https://travis-ci.org/maghead/maghead)
[![Coverage Status](https://img.shields.io/coveralls/maghead/maghead.svg)](https://coveralls.io/r/maghead/maghead)
[![Latest Stable Version](https://poser.pugx.org/maghead/maghead/v/stable.svg)](https://packagist.org/packages/maghead/maghead) 
[![Total Downloads](https://poser.pugx.org/maghead/maghead/downloads.svg)](https://packagist.org/packages/maghead/maghead) 
[![Monthly Downloads](https://poser.pugx.org/maghead/maghead/d/monthly)](https://packagist.org/packages/maghead/maghead)
[![Daily Downloads](https://poser.pugx.org/maghead/maghead/d/daily)](https://packagist.org/packages/maghead/maghead)
[![Latest Unstable Version](https://poser.pugx.org/maghead/maghead/v/unstable.svg)](https://packagist.org/packages/maghead/maghead) 
[![License](https://poser.pugx.org/maghead/maghead/license.svg)](https://packagist.org/packages/maghead/maghead)
[![Join the chat at https://gitter.im/maghead/maghead](https://badges.gitter.im/maghead/maghead.svg)](https://gitter.im/maghead/maghead?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Works On My Machine](https://cdn.rawgit.com/nikku/works-on-my-machine/v0.2.0/badge.svg)](https://github.com/nikku/works-on-my-machine)
[![Made in Taiwan](https://img.shields.io/badge/made%20in-taiwan-green.svg)](README.md)

**4.0.x IS CURRENTLY UNDER HEAVY DEVELOPMENT, API IS NOT STABLE**

Maghead is an open-source Object-Relational Mapping (ORM) designed for PHP7.

Maghead uses static code generator to generate static classes that maps to the database records and methods, which reduces runtime
costs, therefore it's pretty lightweight and extremely fast.

With the simple schema design, you can define your model schema very easily and
you can even embed closure in your schema classes.

How fast is it? Currently it's the fastest ORM written in pure PHP. [See the benchmark for more details](https://github.com/c9s/forked-php-orm-benchmark).

Automatic Migration Demonstration
--------------------------------
<img src="https://raw.github.com/maghead/maghead/master/assets/images/migration.gif" width="600"/>

Feature
-------

* Fast & Simple
* Configuration based on YAML format and compiled into PHP
* PDO, MySQL, Pgsql, SQLite support.
* Multiple data sources.
* Mix-in model.
* Powerful Migration Generator
  * Upgrade & Downgrade of course.
  * Automatic Migration: generate migration SQL automatically based on the schema diff.
* Schema/Database diff

Design Concept
--------------

- Function calls in PHP are very slow, so the model schema data
  will be built statically, Maghead converts all definitions (default value, validator, filter, valid
  value builder) into classes and static PHP array, this keeps these model
  classes very lightweight and fast.
  
- In the runtime, all the same model objects use the same 
  schema object, and we can reuse the prebuild data from the static schema class.

- We keep base model class constructor empty, so when you are querying data from
  database, these model objects can be created with zero effort.

Getting Started
---------------

Please see the details on [Wiki](https://github.com/maghead/maghead/wiki)


Schema
------------

### Defining Schema Class

Simply extend class from `Maghead\Schema\DeclareSchema`, and define your model columns 
in the `schema` method, e.g.,

```php
<?php
namespace TestApp;
use Maghead\Schema\DeclareSchema;

class BookSchema extends DeclareSchema
{

    public function schema()
    {
        $this->column('title')
            ->unique()
            ->varchar(128);

        $this->column('subtitle')
            ->varchar(256);

        $this->column('isbn')
            ->varchar(128)
            ->immutable();

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
            ->refer('TestApp\UserSchema');


        // Defining trait for model class
        $this->addModelTrait('Uploader');
        $this->addModelTrait('Downloader')
            ->useInsteadOf('Downloader::a', 'Uploader');

        $this->belongsTo('created_by', 'TestApp\UserSchema','id', 'created_by');

        /** 
         * column: author => Author class 
         *
         * $book->publisher->name;
         *
         **/
        $this->belongsTo('publisher','\TestApp\PublisherSchema', 'id', 'publisher_id');

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', '\TestApp\AuthorBookSchema', 'book_id', 'id');


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




#### Defining Mixin Method

```php
namespace Maghead\Schema\Mixin;
use Maghead\Schema\MixinDeclareSchema;

class MetadataMixinSchema extends MixinDeclareSchema
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
$result = $record->fooMethod(1,2,3,4);
```

### Using Multiple Data Source

You can define specific data source for different model in the model schema:

```php
use Maghead\Schema\DeclareSchema;

class UserSchema extends DeclareSchema {

    public function schema() {
        $this->writeTo('master');
        $this->readFrom('slave');
    }

}
```

Or you can specify for both (read and write):

```php
use Maghead\Schema\DeclareSchema;

class UserSchema extends DeclareSchema {

    public function schema() {
        $this->using('master');
    }

}
```


Migration
---------

If you need to modify schema code, like adding new columns to a table, you 
can use the amazing migration feature to migrate your database to the latest
change without pain.

Once you modified the schema code, you can execute `lazy diff` command to compare
current exisiting database table:

    $ maghead diff
    + table 'authors'            tests/tests/Author.php
    + table 'addresses'          tests/tests/Address.php
    + table 'author_books'       tests/tests/AuthorBook.php
    + table 'books'              tests/tests/Book.php
    + table 'users'              tests/tests/User.php
    + table 'publishers'         tests/tests/Publisher.php
    + table 'names'              tests/tests/Name.php
    + table 'wines'              tests/tests/Wine.php

As you can see, we added a lot of new tables (schemas), and Maghead parses
the database tables to show you the difference to let you know current
status.

> Currently Maghead supports SQLite, PostgreSQL, MySQL table parsing.

now you can generate the migration script or upgrade database schema directly.

to upgrade database schema directly, you can simply run:

    $ maghead migrate auto

to upgrade database schema through a customizable migration script, you can 
generate a new migration script like:

    $ maghead migrate diff AddUserRoleColumn
    Loading schema objects...
    Creating migration script from diff
    Found 10 schemas to compare.
        Found schema 'TestApp\AuthorSchema' to be imported to 'authors'
        Found schema 'TestApp\AddressSchema' to be imported to 'addresses'
        Found schema 'TestApp\AuthorBookSchema' to be imported to 'author_books'
        Found schema 'TestApp\BookSchema' to be imported to 'books'
        Found schema 'TestApp\UserSchema' to be imported to 'users'
        Found schema 'TestApp\PublisherSchema' to be imported to 'publishers'
        Found schema 'TestApp\NameSchema' to be imported to 'names'
        Found schema 'TestApp\Wine' to be imported to 'wines'
    Migration script is generated: db/migrations/20120912_AddUserRoleColumn.php

now you can edit your migration script, which is auto-generated:

    vim db/migrations/20120912_AddUserRoleColumn.php

the migration script looks like:

```php
class AddUserColumn_1347451491  extends \Maghead\Migration\Migration {

    public function upgrade() { 
        $this->importSchema(new TestApp\AuthorSchema);
        $this->importSchema(new TestApp\AddressSchema);

        // To upgrade with new schema:
        $this->importSchema(new TestApp\AuthorBookSchema);
        
        // To create index:
        $this->createIndex($table,$indexName,$columnNames);
        
        // To drop index:
        $this->dropIndex($table,$indexName);
        
        // To add a foreign key:
        $this->addForeignKey($table,$columnName,$referenceTable,$referenceColumn = null) 
        
        // To drop table:
        $this->dropTable('authors');
    }

    public function downgrade() { 

        $this->dropTable('authors');
        $this->dropTable('addresses');
        
    }
}
```

The built-in migration generator not only generates the upgrade script,
but also generates the downgrade script, you can modify it to anything as you
want.

After the migration script is generated, you can check the status of 
current database and waiting migration scripts:

    $ maghead migrate status
    Found 1 migration script to be executed.
    - AddUserColumn_1347451491

now you can run upgrade command to 
upgrade database schema through the migration script:

    $ maghead migrate up

If you regret, you can run downgrade migrations through the command:

    $ maghead migrate down

But please note that SQLite doesn't support column renaming and column
dropping.

To see what migration script could do, please check the documentation of
Magsql package.

### A More Advanced Model Schema

```php
use Maghead\Schema\DeclareSchema;

class AuthorSchema extends DeclareSchema
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


LICENSE
===============

MIT License
