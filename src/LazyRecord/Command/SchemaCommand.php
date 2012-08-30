<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class SchemaCommand extends Command
{

    function init()
    {
        parent::init();
        $this->registerCommand('build',    'LazyRecord\Command\BuildSchemaCommand');
        $this->registerCommand('sql',    'LazyRecord\Command\BuildSqlCommand');
        $this->registerCommand('list',    'LazyRecord\Command\ListSchemaCommand');
    }

    function execute() { 
   
    }
}


