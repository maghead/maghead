<?php

namespace Maghead\Console\Command\ConfigCommand;

use CLIFramework\Command;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Config\AutoConfigLoader;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Bootstrap;
use Maghead\Runtime\Config\Config;
use Maghead\Utils;

class InitCommand extends Command
{
    public function options($opts)
    {
        $opts->add('driver:', 'pdo driver type');
        $opts->add('dsn:', 'dsn');
        $opts->add('database:', 'database name');
        $opts->add('username:', 'username');
        $opts->add('password:', 'password');
        $opts->add('c|config:', 'config file');
    }

    public function execute()
    {
        $logger = $this->getLogger();

        $configFile = $this->options->config ?: Bootstrap::DEFAULT_CONFIG_FILE;
        if (file_exists($configFile)) {
            $logger->info("Config file $configFile already exists.");
            return;
        }

        $driver = $this->options->driver ?: $this->ask('Database driver [sqlite]', array('sqlite', 'pgsql', 'mysql', null)) ?: 'sqlite';
        $dbName = $this->options->database ?: $this->ask('Database name [:memory:]') ?: ':memory:';

        $logger->info("Using $driver driver");
        $logger->info("Using database $dbName");

        $user = '';
        $password = '';
        if ($driver != 'sqlite') {
            // FIXME: fix DSN for sqlite, "sqlite:mydb.sqlite3" doesn't require dbname= ...
            $user = $this->options->username ?: $this->ask('Database user');
            $password = $this->options->password ?: $this->ask('Database password');
        }
        $logger->info('Creating config file skeleton...');
        $content = <<<EOS
---
cli:
  bootstrap: vendor/autoload.php
schema:
  # Insert auto-increment primary key to every schema classes
  auto_id: true
  # Customize your schema class loader
  #
  # loader: custom_schema_loader.php
  #  Customize your schema paths
  # paths:
  # - src
databases:
  master:
    driver: $driver
    database: $dbName
    user: $user
    pass: $password
EOS;
        if (file_put_contents($configFile, $content) !== false) {
            $logger->info("Config file is generated: $configFile");
        }

        $this->logger->info("Building config from $configFile");
        $dir = dirname($configFile);
        ConfigLoader::compile($configFile, true);

        // make master config link
        $cleanup = [SymbolicLinkConfigLoader::ANCHOR_FILENAME, '.lazy.php', '.lazy.yml'];
        foreach ($cleanup as $symlink) {
            if (file_exists($symlink)) {
                $this->logger->debug('Cleaning up symbol link: '.$symlink);
                unlink($symlink);
            }
        }

        $this->logger->info('Creating symbol link: '.SymbolicLinkConfigLoader::ANCHOR_FILENAME.' -> '.$configFile);
        if (Utils::symlink($configFile, SymbolicLinkConfigLoader::ANCHOR_FILENAME) === false) {
            $this->logger->error('Config linking failed.');
        }
        $this->logger->info('Done');
    }
}
