<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;
use Maghead\DSN\DSNParser;
use PDO;

class AddCommand extends BaseCommand
{
    public function brief()
    {
        return 'Add a database to the config file.';
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('create', 'invoke create database query');
        $opts->add('host:', 'host for database');
        $opts->add('port:', 'port for database');
        $opts->add('user:', 'user id for database connection');
        $opts->add('password:', 'password for database connection');
        $opts->add('dbname:', 'databasename');
    }

    public function arguments($args)
    {
        $args->add('node-id');
        $args->add('dsn');
    }

    public function execute($nodeId, $dsnStr)
    {
        // force loading data source
        $config = $this->getConfig(true);
        $configManager = new ConfigManager($config);

        $nodeOptions = [];

        if ($this->options->host) {
            $nodeOptions['host'] = $this->options->host;
        }
        if ($this->options->port) {
            $nodeOptions['port'] = $this->options->port;
        }
        if ($this->options->database) {
            $nodeOptions['database'] = $this->options->database;
        }
        if ($this->options->user) {
            $nodeOptions['user'] = $this->options->user;
        }
        if ($this->options->password) {
            $nodeOptions['password'] = $this->options->password;
        }

        $nodeConfig = $configManager->addDatabase($nodeId, $dsnStr, $nodeOptions);
        $configManager->save();

        if ($this->options->create) {
            $cmd = $this->createCommand('Maghead\\Command\\DbCommand\\CreateCommand');
            return $cmd->execute($nodeId);
        }
        return true;
    }
}
