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
            $this->logger->info($path);
            mkdir($path, 0755, true);
        }
    }

    public function execute()
    {
        $this->mkpath('db/config');
        $this->mkpath('db/migration');
        $command = $this->createCommand('Maghead\\Command\\InitConfCommand');
        $command->execute();
        $command = $this->createCommand('Maghead\\Command\\BuildConfCommand');
        $command->execute('db/config/database.yml');
    }
}
