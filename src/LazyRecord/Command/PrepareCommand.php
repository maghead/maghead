<?php
namespace LazyRecord\Command;
use CLIFramework\ChainedCommand;
use LazyRecord\Command\BuildSchemaCommand;
use LazyRecord\Command\BuildSqlCommand;
use LazyRecord\Command\BuildBasedataCommand;

class PrepareCommand extends ChainedCommand
{
    public $commands = array(
        'LazyRecord\\Command\\BuildSchemaCommand',
        'LazyRecord\\Command\\BuildSqlCommand',
        'LazyRecord\\Command\\BuildBasedataCommand',
    );

    public function usage() { return 'lazy prepare'; }

    public function brief() { return 'prepare schema and database.'; }
}

