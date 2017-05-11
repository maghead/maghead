<?php

namespace Maghead\Console\Command\SchemaCommand;

use Maghead\Console\Command\BaseCommand;
use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Schema\SchemaUtils;

/**
 * $ maghead schema build path/to/Schema path/to/SchemaDir.
 */
class BuildCommand extends BaseCommand
{
    public function usage()
    {
        return 'schema build [paths|classes]';
    }

    public function brief()
    {
        return 'build schema files.';
    }

    public function arguments($args)
    {
        $args->add('file')
            ->isa('file')
            ;
    }

    public function options($opts)
    {
        $opts->add('f|force', 'force generate all schema files.');
        parent::options($opts);
    }

    public function execute()
    {
        $args = func_get_args();
        $config = $this->getConfig();

        $this->logger->debug('Finding schemas...');
        $schemas = $this->loadSchemasFromArguments($args);

        $generator = new SchemaGenerator($config);
        if ($this->options->force) {
            $generator->setForceUpdate(true);
        }

        $classMap = array();
        foreach ($schemas as $schema) {
            $cls = get_class($schema);

            if ($this->logger->isDebug()) {
                $this->logger->debug("Checking $cls");
            }

            if ($schema->virtual) {
                $this->logger->info("Skipping virtual $cls");
                continue;
            }

            $generated = $generator->generateSchemaFiles($schema);
            if (!empty($generated)) {
                if ($this->logger->isDebug()) {
                    // $filepath = str_replace(getcwd().'/', '', $schema->getClassFileName());
                    $this->logger->debug('Updated '.get_class($schema));
                    foreach ($generated as $class => $file) {
                        $this->logger->debug(' - Updated '.$file);
                    }
                } else {
                    $this->logger->info('Updated '.get_class($schema));
                }
                $classMap += $generated;
            }
        }
    }
}
