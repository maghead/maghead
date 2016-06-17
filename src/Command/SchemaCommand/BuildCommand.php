<?php

namespace LazyRecord\Command\SchemaCommand;

use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\SchemaUtils;
use CLIFramework\Logger\ActionLogger;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir.
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
        $logger = $this->getLogger();

        $config = $this->getConfigLoader();

        $this->logger->debug('Finding schemas...');
        $schemas = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), func_get_args(), $this->logger);

        $generator = new SchemaGenerator($config);
        if ($this->options->force) {
            $generator->setForceUpdate(true);
        }

        $actionLogger = new ActionLogger(STDERR);

        // for generated class source code.
        $this->logger->debug('Setting up error handler...');
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            printf("ERROR %s:%s  [%s] %s\n", $errfile, $errline, $errno, $errstr);
        }, E_ERROR);

        $classMap = array();
        foreach ($schemas as $schema) {
            if ($this->logger->isVerbose()) {
                $actionLog = $actionLogger->newAction(get_class($schema), get_class($schema));
                $actionLog->setActionColumnWidth(50);
            } elseif ($this->logger->isDebug()) {
                $filepath = str_replace(getcwd().'/', '', $schema->getClassFileName());
                $actionLog = $actionLogger->newAction($filepath, get_class($schema));
                $actionLog->setActionColumnWidth(50);
            } else {
                $actionLog = $actionLogger->newAction($schema->getShortClassName(), get_class($schema));
            }
            $actionLog->setStatus('checking');

            $generated = $generator->generateSchemaFiles($schema);
            if (!empty($generated)) {
                $actionLog->setStatus('updated');
                $classMap += $generated;
            } else {
                $actionLog->setStatus('skipped');
            }

            $actionLog->finalize();
        }

        $this->logger->debug('Restoring error handler...');
        restore_error_handler();
    }
}
