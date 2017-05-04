<?php

namespace Maghead\Command;

use Exception;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\ConfigWriter;
use CLIFramework\Command;

function cross_symlink($sourcePath, $targetPath)
{
    if (PHP_OS == 'WINNT') {
        return link($sourcePath, $targetPath);
    } else {
        return symlink($sourcePath, $targetPath);
    }
}

class UseCommand extends Command
{
    public function brief()
    {
        return 'Build and use configuration file.';
    }

    public function options($opts)
    {
        $opts->add('f|force', 'force building config file.');
        $opts->add('s|search', 'search default config file automatically');
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
        /*
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         *
         * build/lazy/config.php   # is generated
         */
        if (!$configFile && $this->options->{'search'}) {
            $possiblePaths = array(
                'db/config/site_database.yml',
                'db/config/database.yml',
                'config/database.yml',
                'config/site_database.yml',
            );
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $this->logger->info("Found default config file: $path");
                    $configFile = $path;
                    FileConfigLoader::compile($configFile);
                }
            }
        }

        if (!$configFile) {
            throw new Exception('default config file was not found, however config file is required.');
        }

        $this->logger->info("Building config from $configFile");
        $dir = dirname($configFile);
        FileConfigLoader::compile($configFile, $this->options->force);

        // make master config link
        $cleanup = [SymbolicLinkConfigLoader::ANCHOR_FILENAME, '.lazy.php', '.lazy.yml'];
        foreach ($cleanup as $symlink) {
            if (file_exists($symlink)) {
                $this->logger->debug('Cleaning up symbol link: '.$symlink);
                unlink($symlink);
            }
        }

        $this->logger->info('Creating symbol link: '.SymbolicLinkConfigLoader::ANCHOR_FILENAME.' -> '.$configFile);
        if (cross_symlink($configFile, SymbolicLinkConfigLoader::ANCHOR_FILENAME) === false) {
            $this->logger->error('Config linking failed.');
        }
        $this->logger->info('command line environment is now configured.');
    }
}
