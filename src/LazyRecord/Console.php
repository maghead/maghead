<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
	public function init()
	{
		parent::init();
        $this->registerCommand('build-conf', 'BuildConfCommand');
        $this->registerCommand('schema', 'BuildSchemaCommand');
        $this->registerCommand('sql', 'BuildSqlCommand');
	}
}
