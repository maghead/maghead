<?php

namespace Maghead\Console\Command\DbCommand;

class RecreateCommand extends CreateCommand
{
    public function brief()
    {
        return 're-create database bases on the current config.';
    }

    public function execute($nodeId = 'master')
    {
        $dropCommand = $this->createCommand(DropCommand::class);
        $dropCommand->setOptions($this->options);
        $dropCommand->execute($nodeId);
        $ret = parent::execute($nodeId);

        $this->logger->info("Database $nodeId is re-created successfully.");
        return $ret;
    }
}
