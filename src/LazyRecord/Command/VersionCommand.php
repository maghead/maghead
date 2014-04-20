<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;

class VersionCommand extends Command
{
    public function brief() { return 'Show database version'; }

    public function usage() { 
        return "\tlazy version\n";
    }

    public function options($opts)
    {
        $opts->add('D|data-source:', 'specify data source id');
    }

    public function execute() 
    {
        $dsId = $this->options->{'data-source'} ?: 'default';
        CommandUtils::init_config_loader();
        $meta = new Metadata($dsId);
        $this->logger->info("database version: " . $meta['version']);
    }
}



