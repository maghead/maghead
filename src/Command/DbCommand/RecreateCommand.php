<?php

namespace Maghead\Command\DbCommand;

class RecreateCommand extends CreateCommand
{
    public function brief()
    {
        return 're-create database bases on the current config.';
    }

    public function execute()
    {
        $dropCommand = $this->createCommand('Maghead\Command\DbCommand\DropCommand');
        $dropCommand->options = $this->options;
        $dropCommand->execute();
        parent::execute();
    }
}
