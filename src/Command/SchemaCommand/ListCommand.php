<?php

namespace Maghead\Command\SchemaCommand;

use Maghead\ConfigLoader;
use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Schema\SchemaUtils;
use Maghead\Command\BaseCommand;
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
        $classes = SchemaUtils::findSchemasByArguments(
            $config,
            $args,
            $this->logger);

        foreach ($classes as $class) {
            $rfc = new ReflectionClass($class);
            $this->logger->info(
                sprintf('  %-50s %s', $class, $rfc->getFilename()));
        }
        $logger->info('Done');
    }
}
