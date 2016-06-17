<?php

namespace LazyRecord\Command\DbCommand;

class RecreateCommand extends CreateCommand
{
    public function brief()
    {
        return 're-create database bases on the current config.';
    }

    public function execute()
    {
        $dropCommand = $this->createCommand('LazyRecord\Command\DbCommand\DropCommand');
        $dropCommand->options = $this->options;
        $dropCommand->execute();
        parent::execute();
    }
}
