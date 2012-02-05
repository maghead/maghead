<?php
namespace LazyRecord;

class BuildConfCommand extends \CLIFramework\Command
{
    public function execute($configFile = 'config/lazy.yml')
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */

    }
}
