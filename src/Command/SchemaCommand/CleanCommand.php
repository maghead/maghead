<?php

namespace Maghead\Command\SchemaCommand;

use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Schema\SchemaUtils;
use Maghead\Command\BaseCommand;

/**
 * $ lazy clean-schema path/to/Schema path/to/SchemaDir.
 */
class CleanCommand extends BaseCommand
{
    public function usage()
    {
        return 'clean-schema [paths|classes]';
    }

    public function brief()
    {
        return 'clean up schema files.';
    }

    public function options($opts)
    {
        $opts->add('f|force', 'force generate all schema files.');
        parent::options($opts);
    }

    public function execute()
    {
        $config = $this->getConfig(true);

        $this->logger->debug('Finding schemas...');
        $schemas = $this->findSchemasByArguments(func_get_args());

        foreach ($schemas as $schema) {
            $this->logger->info('Cleaning schema '.get_class($schema));
            $paths = array();
            $paths[] = $schema->getRelatedClassPath($schema->getBaseModelClass());
            $paths[] = $schema->getRelatedClassPath($schema->getBaseRepoClass());
            $paths[] = $schema->getRelatedClassPath($schema->getBaseCollectionClass());
            $paths[] = $schema->getRelatedClassPath($schema->getSchemaProxyClass());

            foreach ($paths as $path) {
                $this->logger->info(' - Deleting '.$path);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        $this->logger->info('Done');
    }
}
