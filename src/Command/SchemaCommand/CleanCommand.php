<?php

namespace Maghead\Command\SchemaCommand;

use Maghead\Schema\SchemaGenerator;
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
        $logger = $this->getLogger();

        $config = $this->getConfigLoader();

        $this->logger->debug('Finding schemas...');
        $schemas = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), func_get_args(), $this->logger);

        foreach ($schemas as $schema) {
            $this->logger->info('Cleaning schema '.get_class($schema));
            $paths = array();
            $paths[] = $schema->getRelatedClassPath($schema->getBaseModelClass());
            $paths[] = $schema->getRelatedClassPath($schema->getBaseCollectionClass());
            $paths[] = $schema->getRelatedClassPath($schema->getSchemaProxyClass());

            foreach ($paths as $path) {
                $this->logger->info(' - Deleting '.$path);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        /*
        $generator = new SchemaGenerator($config, $this->logger);
        if ( $this->options->force ) {
            $generator->setForceUpdate(true);
        }
        $classMap = $generator->generate($classes);
         */

        /*
        foreach( $classMap as $class => $file ) {
            $path = $file;
            if ( strpos( $path , getcwd() ) === 0 ) {
                $path = substr( $path , strlen(getcwd()) + 1 );
            }
            $logger->info($path);
            // $logger->info(sprintf("%-32s",ltrim($class,'\\')) . " => $path",1);
        }
        */
        $logger->info('Done');
    }
}
