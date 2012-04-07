<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    public function init()
    {
        parent::init();
        $this->registerCommand('init-conf',    'LazyRecord\Command\InitConfCommand');
        $this->registerCommand('build-conf',   'LazyRecord\Command\BuildConfCommand');
        $this->registerCommand('build-schema', 'LazyRecord\Command\BuildSchemaCommand');
        $this->registerCommand('build-sql',    'LazyRecord\Command\BuildSqlCommand');
        $this->registerCommand('diff',         'LazyRecord\Command\DiffCommand');
    }
}
