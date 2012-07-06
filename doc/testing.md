Testing
=======

## Config

To test LazyRecord ORM, first you need to copy the config for test:

    cp -v config/database.testing.yml config/database.yml


## Test with MySQL database

To test with mysql database:

    $ mysql -uroot -p
    > create database testing charset utf8;
    > grant all privileges on testing.* to 'testing'@'localhost' identified by 'testing';

And this should work.

## Test with PostgreSQL database

To test with pgsql database:

    $ sudo -u postgres createuser --no-createrole --no-superuser --pwprompt testing
    $ sudo -u postgres createdb -E=utf8 --owner=testing testing


## Build Schema files

    $ php bin/lazy build-schema

## Build SQL 

We've already defined 3 data sources, they were named as 'mysql', 'pgsql', 'sqlite' , 
now you can insert schema sqls into these data sources:

    $ php bin/lazy build-sql --rebuild -D=mysql
    $ php bin/lazy build-sql --rebuild -D=pgsql
    $ php bin/lazy build-sql --rebuild -D=sqlite

## Run PHPUnit

    $ phpunit

