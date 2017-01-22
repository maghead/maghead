
## Install Bundles

Use Onion to install dependent libraries

    $ onion -d install

## Setup for Maghead development 

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

When tests rans successful, you can run the below script to compile library
files into the executable phar file `lazy`:

    $ bash scripts/compile.sh

To build sql with specific data source:

    php bin/maghead build-sql --data-source=mysql  --rebuild
    php bin/maghead build-sql --data-source=master --rebuild
    php bin/maghead build-sql --data-source=slave  --rebuild

