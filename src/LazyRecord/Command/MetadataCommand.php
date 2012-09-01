<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;

class MetadataCommand extends Command
{
    public function execute() 
    {
        CommandUtils::init_config_loader();

        $args = func_get_args();
        if(empty($args)) {
            $meta = new Metadata('default');
            $meta->init();
            foreach( $meta as $key => $value ) {
                printf("%-20s %-20s\n", $key, $value);
            }
        }
    }
}



