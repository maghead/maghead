<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;

use Maghead\Sharding\Operations\PruneShard;
use Maghead\ConfigWriter;

class PruneCommand extends BaseCommand
{
    public function brief()
    {
        return 'prune a shard';
    }

    public function arguments($args)
    {
        $args->add('shardID');
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('mapping:', 'the shard mapping where the new shard will be added to.');
    }

    public function execute($shardId)
    {
        $config = $this->getConfig(true);

        $o = new PruneShard($config);
        $o->prune($shardId, $this->options->mapping);

        ConfigWriter::write($config);
    }
}
