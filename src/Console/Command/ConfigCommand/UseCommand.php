<?php

namespace Maghead\Console\Command\ConfigCommand;

use Exception;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Bootstrap;
use Maghead\Utils;

use CLIFramework\Command;

class UseCommand extends Command
{
    public function brief()
    {
        return 'build and use configuration file.';
    }

    public function options($opts)
    {
        $opts->add('f|force', 'force building config file.');
    }

    public function arguments($args)
    {
        $args->add('file')
            ->isa('file')
            ->glob('*.yml')
            ->optional()
            ;
    }

    public function execute($configFile = null)
    {
        if (!$configFile) {
            $possiblePaths = [
                Bootstrap::DEFAULT_SITE_CONFIG_FILE,
                Bootstrap::DEFAULT_CONFIG_FILE,
                'config/database.yml',
                'config/site_database.yml',
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $this->logger->info("Found default config file: $path");
                    $configFile = $path;
                    FileConfigLoader::load($configFile, true);
                }
            }
        }

        Utils::mkpath(['db/config', 'db/migration'], 0755, $this->logger);

        if (!$configFile) {
            throw new Exception('default config file was not found, however config file is required.');
        }

        $this->logger->info("Building config from $configFile");
        $dir = dirname($configFile);
        FileConfigLoader::load($configFile, $this->options->force);

        // make master config link
        $cleanup = [SymbolicLinkConfigLoader::ANCHOR_FILENAME, '.lazy.php', '.lazy.yml'];
        foreach ($cleanup as $symlink) {
            if (file_exists($symlink)) {
                $this->logger->debug('Cleaning up symbol link: '.$symlink);
                unlink($symlink);
            }
        }

        $this->logger->info('Creating symbol link: '.SymbolicLinkConfigLoader::ANCHOR_FILENAME.' -> '.$configFile);
        if (Utils::symlink($configFile, SymbolicLinkConfigLoader::ANCHOR_FILENAME) === false) {
            $this->logger->error('Config linking failed.');
        }
        $this->logger->info('command line environment is now configured.');
    }
}
