<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Console\Command\BaseCommand;

class SchemaCommand extends BaseCommand
{
    public function brief()
    {
        return 'schema commands';
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
        $diff = $this->createCommand('Maghead\\Console\\Command\\DiffCommand');
        $diff->logger = $diff->logger;
        $diff->options($opts);
    }

    public function execute()
    {
        $args = func_get_args();

        $buildCommand = $this->getCommand('build');
        $buildCommand->executeWrapper($args);

        $diffCommand = $this->createCommand('Maghead\\Console\\Command\\DiffCommand');
        $diffCommand->setOptions($this->options);
        $diffCommand->executeWrapper(array());
    }
}
