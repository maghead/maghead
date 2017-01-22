<?php

namespace Maghead\Command\SchemaCommand;

use Maghead\ConfigLoader;
use Maghead\Schema\SchemaGenerator;
use Maghead\Utils;
use CLIFramework\Command;
use ReflectionClass;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir.
 */
class ListCommand extends Command
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
        $loader = ConfigLoader::getInstance();
        $loader->loadFromSymbol(true);
        $loader->initForBuild();

        $this->logger->debug('Initializing schema generator...');
        $generator = new SchemaGenerator($loader, $logger);

        $args = func_get_args();
        $classes = Utils::findSchemasByArguments(
            $loader,
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
