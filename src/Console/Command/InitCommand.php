<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Runtime\Bootstrap;

class InitCommand extends Command
{
    public function brief()
    {
        return 'initialize your maghead project structures.';
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

        $defaultConfigFile = Bootstrap::DEFAULT_CONFIG_FILE;
        if (file_exists($defaultConfigFile)) {
            $command = $this->createCommand('Maghead\\Console\\Command\\UseCommand');
            $command->execute($defaultConfigFile);
        } else {
            // If the default database config file is not found, create one.
            $command = $this->createCommand('Maghead\\Console\\Command\\InitConfCommand');
            $command->execute();
        }
    }
}
