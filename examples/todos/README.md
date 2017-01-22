Todos App
==========

Installation
------------

    pear channel-discover pear.corneltek.com
    pear install -a corneltek/Maghead

Setup
------

Simply run `lazy` to see the help message

    $ lazy

    Maghead ORM
    
    Usage
    	lazy [options] [command] [argument1 argument2...]
    
    Options
               -v, --verbose   Print verbose message.
                 -d, --debug   Print debug message.
                 -q, --quiet   Be quiet.
                  -h, --help   help
                   --version   show version
    
    Commands
                        help   show help message of a command
                        init   initialize your lazyrecord structures.
                  build-conf   build configuration file.
                      schema   schema command.
                 list-schema   list schema files.
                build-schema   build configuration file.
              build-basedata   insert basedata into datasource.
                   build-sql   build sql and insert into database.
                        diff   diff database schema.
                     migrate   migrate database schema.
                     prepare   prepare schema and database.
                    metadata   set, get or list metadata.
                   create-db   create database from config

Build config, this step compiles db/config/database.yml to db/config/database.php
and it creates a symlink from db/config/database.yml, the symlink file is for lazyrecord 
command to load.

    $ lazy build-conf

Build static schema classes:

    $ lazy build-schema
    Finding schemas...
    Found schema classes
    Initializing schema generator...
    	Todos\Model\TodoSchemaProxy      => src/Todos/Model/TodoSchemaProxy.php
    	Todos\Model\TodoCollectionBase   => src/Todos/Model/TodoCollectionBase.php
    	Todos\Model\TodoCollection       => src/Todos/Model/TodoCollection.php
    	Todos\Model\Todo                 => src/Todos/Model/Todo.php
    Done

Create database (database name from config):

    $ lazy create-db

Build Schema SQL to database:

    lazy build-sql --rebuild --basedata

Run
---

To run example application, simply run the app.php in command-line mode:

    php app.php


More Details
------------

Please checkout 

1. README.md file in Maghead.
2. documentation in `doc/`.
3. model test code in `tests/Maghead/ModelTest/`.
4. collection test code in `tests/Maghead/ColllectionTest`.
5. model schema files in `tests`.
6. #php-tw IRC channel.

