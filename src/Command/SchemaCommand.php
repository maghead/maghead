<?php

namespace Maghead\Command;

use CLIFramework\Command;

class SchemaCommand extends Command
{
    public function brief()
    {
        return 'schema command.';
    }

    public function init()
    {
        parent::init();
        $this->command('build');
        $this->command('list');
        $this->command('clean');
        $this->command('status');
    }

    public function options($opts)
    {
        $diff = $this->createCommand('Maghead\\Command\\DiffCommand');
        $diff->logger = $diff->logger;
        $diff->options($opts);
    }

    public function execute()
    {
        $args = func_get_args();

        $buildCommand = $this->getCommand('build');
        $buildCommand->options = $this->options;
        $buildCommand->executeWrapper($args);

        $diffCommand = $this->createCommand('Maghead\\Command\\DiffCommand');
        $diffCommand->options = $this->options;
        $diffCommand->executeWrapper(array());
        // $this->logger->info('Usage: schema [build|sql|list]');
    }
}
