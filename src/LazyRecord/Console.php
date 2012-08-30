<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'LazyRecord';
    const version = '1.4.0';

    public function brief()
    {
        return 'lazy [command]';
    }

    public function init()
    {
        parent::init();
        $this->registerCommand('init-conf',    'LazyRecord\Command\InitConfCommand');
        $this->registerCommand('build-conf',   'LazyRecord\Command\BuildConfCommand');

        $this->registerCOmmand('schema',  'LazyRecord\Command\SchemaCommand');
        $this->registerCOmmand('list-schema',  'LazyRecord\Command\ListSchemaCommand');
        $this->registerCommand('build-schema', 'LazyRecord\Command\BuildSchemaCommand');

        $this->registerCommand('build-basedata', 'LazyRecord\Command\BuildBaseDataCommand');
        $this->registerCommand('build-sql',    'LazyRecord\Command\BuildSqlCommand');
        $this->registerCommand('diff',         'LazyRecord\Command\DiffCommand');

        $this->registerCommand('create-db',         'LazyRecord\Command\CreateDBCommand');
    }
}
