# Development

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
