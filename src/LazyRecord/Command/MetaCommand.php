<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;

class MetaCommand extends BaseCommand
{
    public function brief() { return 'Set, get or list meta.'; }

    public function usage() { 
        return 
              "\tlazy meta\n"
            . "\tlazy meta [key] [value]\n"
            . "\tlazy meta [key]\n";
    }

    public function execute() 
    {
        $dsId = $this->getCurrentDataSourceId();
        $queryDriver = $this->getCurrentQueryDriver();
        $conn = $this->getCurrentConnection();
        

        $args = func_get_args();
        if(empty($args)) {
            $meta = new Metadata($queryDriver, $conn);
            printf("%26s | %-20s\n",'Key','Value');
            printf("%s\n", str_repeat('=',50));
            foreach( $meta as $key => $value ) {
                printf("%26s   %-20s\n", $key, $value);
            }
        } else if (count($args) == 1) {
            $key = $args[0];
            $meta = new Metadata($queryDriver, $conn);
            $value = $meta[$key];
            $this->logger->info("$key = $value");
        } else if (count($args) == 2) {
            list($key,$value) = $args;
            $this->logger->info("Setting meta $key to $value.");
            $meta = new Metadata($queryDriver, $conn);
            $meta[$key] = $value;
        }
    }
}



