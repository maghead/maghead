<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;

class MetaCommand extends Command
{

    public function brief() { return 'set, get or list meta.'; }

    public function usage() { 
        return 
              "\tlazy meta\n"
            . "\tlazy meta [key] [value]\n"
            . "\tlazy meta [key]\n";
    }

    public function options($opts)
    {
        $opts->add('D|data-source:', 'specify data source id');
    }

    public function execute() 
    {
        $dsId = $this->options->{'data-source'} ?: 'default';

        CommandUtils::init_config_loader();

        $args = func_get_args();
        if(empty($args)) {
            $meta = new Metadata($dsId);
            printf("%26s | %-20s\n",'Key','Value');
            printf("%s\n", str_repeat('=',50));
            foreach( $meta as $key => $value ) {
                printf("%26s   %-20s\n", $key, $value);
            }
        }
        elseif( count($args) == 1 ) {
            $key = $args[0];
            $meta = new Metadata($dsId);
            $value = $meta[$key];
            $this->logger->info("$key = $value");
        }
        elseif( count($args) == 2 ) {
            list($key,$value) = $args;
            $this->logger->info("Setting meta $key to $value.");
            $meta = new Metadata($dsId);
            $meta[$key] = $value;
        }
    }
}



