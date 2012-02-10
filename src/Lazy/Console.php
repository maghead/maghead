<?php
namespace Lazy;
use CLIFramework\Application;

class Console extends Application
{
    public function init()
    {
        parent::init();
        $this->registerCommand('init-conf',    'Lazy\Command\InitConfCommand');
        $this->registerCommand('build-conf',   'Lazy\Command\BuildConfCommand');
        $this->registerCommand('build-schema', 'Lazy\Command\BuildSchemaCommand');
        $this->registerCommand('build-sql',    'Lazy\Command\BuildSqlCommand');
    }
}
