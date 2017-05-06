<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;

class RemoveCommand extends BaseCommand
{
    public function brief()
    {
        return 'remove node from config file.';
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('drop', 'perform drop database query before removing the database.');
    }

    public function arguments($args)
    {
        $args->add('node-id');
    }

    public function execute($nodeId)
    {
        $config = $this->getConfig(true);

        if ($this->options->drop) {
            $cmd = $this->createCommand('Maghead\\Command\\DbCommand\\DropCommand');
            $cmd->execute($nodeId);
        }

        $manager = new ConfigManager($config);
        $manager->removeDatabase($nodeId);
        $manager->save();

        $this->logger->info("Database $nodeId is removed successfully.");
        return true;
    }
}
