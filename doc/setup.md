Setup
======

## Install

use git to clone this repository, then

use PEAR to install this package:

    $ onion build
    $ pear install -f package.xml

Make sure that you have `lazy` binary in your path:

    $ which lazy
    /Users/c9s/.phpbrew/php/php-5.4.0/bin/lazy

## Setup for LazyRecord development 

Copy testing env config file:

    $ cp config/database.yml.testing config/database.yml

Create MySql database `lazy_test`:

    $ mysql -uroot -p
    > create database lazy_test charset utf8;

Create PgSQL database `lazy_test`:

    $ createuser --pwprompt lazy
    $ createdb --owner lazy lazy_test

Modify/Update database config file:

    $ vim config/database.yml

Build config file:

    $ lazy build-conf config/database.yml

Run phpunit to test:

    $ phpunit tests

## Setup for your application

### Create config file

Navigate to your application dir, put your configuration file in `config/database.yml` file:

    ---
    schema:
      paths:
        - path/to/schema
    data_sources:
      default:
        dsn: 'sqlite:tests.db'

configure your data source:

DSN for mysql:

    default:
      dsn: 'mysql:host=localhost;dbname=test_db'
      user: root
      pass: 123123

DSN for SQLite:

    default:
      dsn: 'sqlite:tests.db'

DSN for SQLite in memory:

    default:
      dsn: 'sqlite::memory:'

DSN for pgsql:

    default:
      dsn: 'pgsql:host=localhost;dbname=test_db'
      user: root
      pass: 123123

### Compile your config file

    $ lazy build-conf config/database.yml

This will create:

    config/database.yaml.php
    .lazy.php (symlink)


### Build or Update schema files

    $ lazy build-schema

### Build SQL

    $ lazy build-sql

