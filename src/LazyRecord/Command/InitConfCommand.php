<?php
namespace LazyRecord\Command;
use Exception;
use CLIFramework\Command;

class InitConfCommand extends Command
{

    public function brief()
    {
        return 'init configuration file.';
    }

    public function execute($configFile = 'config/database.yml')
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */
        $options = $this->options;
        $logger = $this->logger;

        if( file_exists($configFile) ) {
            $logger->info("Config file $configFile already exists.");
            return;
        }

        $driver = $this->ask('Database driver [sqlite]',array('sqlite','pgsql','mysql',null)) ?: 'sqlite';

        $dbName = $this->ask('Database name [:memory:]') ?: ':memory:';

        $logger->info("Use $driver driver");
        $logger->info("Use database $dbName");
        $logger->info("DSN: $driver:$dbName");

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
  loader: custom_schema_loader.php
  paths:
    - tests/schema
data_sources:
  default:
    dsn: '$driver:$dbName'
    user: $user
    pass: $password
#    slave:
#      dsn: 'mysql:host=localhost;dbname=test'
#      user: root
#      pass: 123123
EOS;
        if( file_put_contents( $configFile , $content ) !== false ) {
            $logger->info("Config file is generated at: $configFile");
        }
    }
}
