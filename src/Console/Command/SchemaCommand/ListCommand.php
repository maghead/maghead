<?php

namespace Maghead\Console\Command\SchemaCommand;

use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Schema\SchemaUtils;
use Maghead\Console\Command\BaseCommand;
use CLIFramework\Command;
use ReflectionClass;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir.
 */
class ListCommand extends BaseCommand
{
    public function usage()
    {
        return 'list-schema [paths|classes]';
    }

    public function brief()
    {
        return 'list schema files.';
    }

    public function execute()
    {
        $logger = $this->getLogger();
        $options = $this->getOptions();

        $this->logger->debug('Loading config');
        $config = $this->getConfig();

        $this->logger->debug('Initializing schema generator...');
        $generator = new SchemaGenerator($config, $logger);

        $args = func_get_args();
        $classes = $this->loadSchemasFromArguments($args);

        foreach ($classes as $class) {
            $rfc = new ReflectionClass($class);
            $this->logger->info(
                sprintf('  %-50s %s', $class, $rfc->getFilename()));
        }
        $logger->info('Done');
    }
}
