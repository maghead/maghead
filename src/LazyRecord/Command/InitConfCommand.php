<?php
namespace LazyRecord\Command;
use Exception;
use CLIFramework\Command;

class InitConfCommand extends Command
{

    public function execute()
    {
        $logger = $this->getLogger();

        $configFile = 'db/config/database.yml';
        if( file_exists($configFile) ) {
            $logger->info("Config file $configFile already exists.");
            return;
        }

        $driver = $this->ask('Database driver [sqlite]',array('sqlite','pgsql','mysql',null)) ?: 'sqlite';
        $dbName = $this->ask('Database name [:memory:]') ?: ':memory:';

        $logger->info("Using $driver driver");
        $logger->info("Using database $dbName");
        $logger->info("Using DSN: $driver:$dbName");

        $user = '';
        $password = '';
        if( $driver != 'sqlite' ) {
            $user = $this->ask('Database user');
            $password = $this->ask('Database password');
        }

        $logger->info("Creating config file skeleton...");
        $content =<<<EOS
---
bootstrap:
  - tests/bootstrap.php
schema:
#  Customize your schema class loader
#
#  loader: custom_schema_loader.php

#  Customize your schema paths
#  paths:
#    - tests/schema
data_sources:
  default:
    driver: $driver
    database: $dbName
    user: $user
    pass: $password
#    slave:
#      dsn: 'mysql:host=localhost;dbname=test'
#      user: root
#      pass: 123123
EOS;
        if( file_put_contents( $configFile , $content ) !== false ) {
            $logger->info("Config file is generated: $configFile");
            $logger->info("Please run build-conf to compile php format config file.");
        }
    }
}
