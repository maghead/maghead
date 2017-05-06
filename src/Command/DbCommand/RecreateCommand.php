<?php

namespace Maghead\Command\DbCommand;

class RecreateCommand extends CreateCommand
{
    public function brief()
    {
        return 're-create database bases on the current config.';
    }

    public function execute($nodeId = 'master')
    {
        $dropCommand = $this->createCommand('Maghead\\Command\\DbCommand\\DropCommand');
        $dropCommand->options = $this->options;
        $dropCommand->execute($nodeId);
        $ret = parent::execute($nodeId);

        $this->logger->info("Database $nodeId is re-created successfully.");
        return $ret;
    }
}
