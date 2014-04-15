<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Command\BuildSchemaCommand;
use LazyRecord\Command\DiffCommand;

class SchemaCommand extends Command
{

    public function brief() { return 'schema command.'; }

    public function init()
    {
        parent::init();
        $this->registerCommand('build',    'LazyRecord\Command\BuildSchemaCommand');
        $this->registerCommand('sql',    'LazyRecord\Command\BuildSqlCommand');
        $this->registerCommand('list',    'LazyRecord\Command\ListSchemaCommand');
    }

    public function options($opts) {
        $diff = $this->createCommand('LazyRecord\Command\DiffCommand');
        $diff->logger = $diff->logger;
        $diff->options($opts);
    }

    public function execute() { 
        $args = func_get_args();

        $buildCommand = $this->getCommand('build');
        $buildCommand->options = $this->options;
        $buildCommand->executeWrapper($args);

        $diffCommand = $this->createCommand('LazyRecord\Command\DiffCommand');
        $diffCommand->options = $this->options;
        $diffCommand->executeWrapper(array());
        // $this->logger->info('Usage: schema [build|sql|list]');
    }
}

