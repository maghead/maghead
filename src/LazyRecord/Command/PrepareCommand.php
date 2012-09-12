<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Command\BuildSchemaCommand;
use LazyRecord\Command\BuildSqlCommand;
use LazyRecord\Command\BuildBasedataCommand;

class PrepareCommand extends Command
{

    public function brief() { return 'prepare schema and database.'; }

    public function options($opts) 
    {
        $cmd1 = new BuildSchemaCommand;
        $cmd2 = new BuildSqlCommand;
        $cmd3 = new BuildBasedataCommand;
        $cmd1->options($opts);
        $cmd2->options($opts);
        $cmd3->options($opts);
    }

    public function execute() 
    {
        $args = func_get_args();
        $app = $this->application;
        $cmd1 = new BuildSchemaCommand($app);
        $cmd2 = new BuildSqlCommand($app);
        $cmd3 = new BuildBasedataCommand($app);

        $logger = $this->getLogger();
        $options = $this->getOptions();
        $cmd1->options = $options;
        $cmd1->logger = $logger;
        $cmd2->options = $options;
        $cmd2->logger = $logger;
        $cmd3->options = $options;
        $cmd3->logger = $logger;

        $cmd1->executeWrapper($args);
        $cmd2->executeWrapper($args);
        $cmd3->executeWrapper($args);
    }
}

