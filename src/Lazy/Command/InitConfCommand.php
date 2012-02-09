<?php
namespace Lazy\Command;
use Exception;
use CLIFramework\Command;

class InitConfCommand extends Command
{

    public function brief()
    {
        return 'init configuration file.';
    }

    public function execute()
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */
        $options = $this->getOptions();
        $logger = $this->getLogger();

        $configFile = 'config/lazy.yml';

        if( file_exists($configFile) ) {
            $logger->info("Config file $configFile already exists.");
            return;
        }

        $logger->info("Creating config file skeleton...");

        $content =<<<EOS
---
bootstrap:
  - tests/bootstrap.php
schema:
  paths:
    - tests/schema
data_sources:
  default:
    dsn: 'sqlite:tests.db'
    # dsn: 'sqlite::memory:'
  mysql:
    dsn: 'mysql:host=localhost;dbname=test'
    user: root
    pass: 123123
EOS;
        if( file_put_contents( $configFile , $content ) !== false ) {
            $logger->info("Config file is generated at: $configFile");
        }
    }
}
