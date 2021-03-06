<?php

namespace Maghead\Console\Command\ShardCommand;

use Maghead\Console\Command\BaseCommand;
use PDO;
use Exception;

use Maghead\Sharding\Operations\PruneShard;
use Maghead\Schema\SchemaUtils;
use Maghead\Runtime\Config\SymbolicLinkConfigWriter;

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

        $schemas = SchemaUtils::findSchemasByConfig($config);

        $o = new PruneShard($config);
        $o->prune($this->options->mapping, $schemas, $shardId);

        SymbolicLinkConfigWriter::write($config);
    }
}
