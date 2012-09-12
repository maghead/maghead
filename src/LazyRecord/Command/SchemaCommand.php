<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class SchemaCommand extends Command
{

    function brief() { return 'schema command.'; }

    function init()
    {
        parent::init();
        $this->registerCommand('build',    'LazyRecord\Command\BuildSchemaCommand');
        $this->registerCommand('sql',    'LazyRecord\Command\BuildSqlCommand');
        $this->registerCommand('list',    'LazyRecord\Command\ListSchemaCommand');
    }

    function execute() { 
        $this->logger->info('Usage: schema [build|sql|list]');
    }
}

