<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;

class CloneCommand extends BaseCommand
{
    public function brief()
    {
        return 'clone a shard';
    }

    public function execute()
    {
        $config = $this->getConfig();
        $dsId = $this->getCurrentDataSourceId();
        $ds = $config->getDataSource($dsId);
    }
}
