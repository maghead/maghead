Setup
======

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

