<?php

namespace Maghead\Command\SchemaCommand;

use Maghead\Command\BaseCommand;
use Maghead\Schema\SchemaUtils;
use CLIFramework\Logger\ActionLogger;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir.
 */
class StatusCommand extends BaseCommand
{
    public function usage()
    {
        return 'schema status';
    }

    public function brief()
    {
        return 'show schema status.';
    }

    public function aliases()
    {
        return ['st'];
    }

    public function arguments($args)
    {
        /*
        $args->add('file')
            ->isa('file')
            ;
         */
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

        $actionLogger = new ActionLogger(STDERR);

        $schemas = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), func_get_args(), $this->logger);
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

            if ($schema->requireProxyFileUpdate()) {
                $actionLog->setStatus('modified', 'yellow');
            } else {
                $actionLog->setStatus('up-to-date');
            }
            $actionLog->finalize();
        }
    }
}
