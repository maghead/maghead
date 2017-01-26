<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;
use Maghead\DSN\DSNParser;
use PDO;

class AddCommand extends BaseCommand
{
    public function brief()
    {
        return 'Add data source to config file.';
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('host:', 'host for database');
        $opts->add('port:', 'port for database');
        $opts->add('user:', 'user id for database connection');
        $opts->add('password:', 'password for database connection');
    }

    public function arguments($args)
    {
        $args->add('data-source-id');
        $args->add('dsn');
    }

    public function execute($dataSourceId, $dsnStr)
    {
        // force loading data source
        $config = $this->getConfig();
        $manager = new ConfigManager($config);
        $manager->addNode($dataSourceId, $dsnStr, [
            'host' => $this->options->host,
            'port' => $this->options->port,
            'dbname' => $this->options->dbname,
            'user' => $this->options->user,
            'password' => $this->options->password,
        ]);
        $manager->save();
        return true;
    }
}
