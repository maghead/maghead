<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;
use Maghead\DSN\DSNParser;
use PDO;

class ListCommand extends BaseCommand
{
    public function brief()
    {
        return 'list databases';
    }

    public function options($opts)
    {
        $opts->add('v|verbose', 'Display verbose information');
    }

    public function execute()
    {
        // force loading data source
        $config = $this->getConfig(true);
        $nodes = $config->getDataSources();
        foreach ($nodes as $id => $config) {
            if ($this->options->verbose) {
                $this->logger->writeln(sprintf('%-10s %s', $id, $config['dsn']));
            } else {
                $this->logger->writeln($id);
            }
        }
        return true;
    }
}
