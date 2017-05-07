<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Runtime\Bootstrap;
use Maghead\Utils;

class InitCommand extends Command
{
    public function brief()
    {
        return 'initialize your maghead project structures.';
    }

    public function execute()
    {
        Utils::mkpath(['db/config', 'db/migration'], 0755, $this->logger);

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
