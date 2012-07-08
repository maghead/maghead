<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use Exception;

class BuildBaseDataCommand extends Command
{

    function brief() { return 'insert basedata into datasource.'; }


}



