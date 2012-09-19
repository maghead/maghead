Todos App
==========

Installation
------------

    pear channel-discover pear.corneltek.com
    pear install -a corneltek/LazyRecord

Setup
------

Build config:

    lazy build-conf

Build static schema classes:

    lazy build-schema

Create database (database name from config):

    lazy create-db

Build Schema SQL to database:

    lazy build-sql --rebuild --basedata

Run
---

To run example application, simply run the app.php in command-line mode:

    php app.php

