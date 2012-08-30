<?php
namespace LazyRecord\Command;
use Exception;
use CLIFramework\Command;

class InitCommand extends Command 
{
    public function brief() {
        return 'initialize your lazyrecord structures.';
    }

    public function mkpath($path) {
        if( ! file_exists($path) ) {
            $this->logger->info($path);
            mkdir($path,0755,true);
        }
    }

    public function execute()
    {
        $this->mkpath('db/config');
        $this->mkpath('db/migration');

        $command = new InitConfCommand;
        $command->application = $this->application;
        $command->execute();

        $command = new BuildConfCommand;
        $command->application = $this->application;
        $command->execute('db/config/database.yml');
    }
}
