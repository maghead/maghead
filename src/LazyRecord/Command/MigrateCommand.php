<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class MigrateCommand extends Command
{

    function options($opts) {
        $opts->add('new:');
        $opts->add('diff:');
    }


    function execute() {

    }
}

