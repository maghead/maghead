<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
	public function init()
	{
		parent::init();
        $this->registerCommand('build-conf', 'LazyRecord\Command\BuildConfCommand');
        $this->registerCommand('schema', 'LazyRecord\Command\BuildSchemaCommand');
        $this->registerCommand('sql', 'LazyRecord\Command\BuildSqlCommand');
	}
}
