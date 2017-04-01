<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;

class SplitCommand extends BaseCommand
{
    public function brief()
    {
        return 'split a shard';
    }

    public function execute()
    {
        $config = $this->getConfig();
        $dsId = $this->getCurrentDataSourceId();
        $ds = $config->getDataSource($dsId);
    }
}
