<?php

namespace Maghead\Command;

use CLIFramework\Command;

class InitCommand extends Command
{
    public function brief()
    {
        return 'initialize your lazyrecord structures.';
    }

    public function mkpath($path)
    {
        if (!file_exists($path)) {
            $this->logger->info("Creating $path");
            mkdir($path, 0755, true);
        }
    }

    public function execute()
    {
        $this->mkpath('db/config');
        $this->mkpath('db/migration');


        $defaultConfigFile = 'db/config/database.yml';
        if (file_exists($defaultConfigFile)) {
            $command = $this->createCommand('Maghead\\Command\\UseCommand');
            $command->execute('db/config/database.yml');
        } else {
            // If the default database config file is not found, create one.
            $command = $this->createCommand('Maghead\\Command\\InitConfCommand');
            $command->execute();
        }
    }
}
