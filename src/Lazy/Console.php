<?php
namespace Lazy;
use CLIFramework\Application;

class Console extends Application
{
	public function init()
	{
		parent::init();
        $this->registerCommand('build-conf', 'Lazy\Command\BuildConfCommand');
        $this->registerCommand('schema', 'Lazy\Command\BuildSchemaCommand');
        $this->registerCommand('sql', 'Lazy\Command\BuildSqlCommand');
	}
}
