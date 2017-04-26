<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;
use Maghead\Sharding\Operations\CloneShard;
use Maghead\ConfigWriter;

class CloneCommand extends BaseCommand
{
    public function brief()
    {
        return 'clone a shard';
    }

    public function options($opts)
    {
        parent::options($opts);

        $opts->add('instance:', 'the instance id')
            ->defaultValue('local');

        $opts->add('drop-first', 'drop first');
    }

    public function arguments($args)
    {
        $args->add('src-shard');
        $args->add('dest-shard');
    }

    public function execute($srcNode, $destNode)
    {
        $config = $this->getConfig(true);

        $o = new CloneShard($config);

        if ($this->options->{"drop-first"}) {
            $o->setDropFirst(true);
        }
        $o->clone($this->options->instance, $destNode, $srcNode);

        ConfigWriter::write($config);
    }
}
