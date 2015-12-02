<?php
namespace LazyRecord\Command\SchemaCommand;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\SchemaUtils;
use CLIFramework\Logger\ActionLogger;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
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
        $opts->add('f|force','force generate all schema files.');
        parent::options($opts);
    }

    public function execute()
    {
        $logger = $this->getLogger();
        $config = $this->getConfigLoader();
        $this->logger->debug('Finding schemas...');

        $actionLogger = new ActionLogger(STDERR);


        $schemas = $this->findSchemasByArguments(func_get_args());
        foreach ($schemas as $schema) {
            $actionLog = $actionLogger->newAction(get_class($schema), '');
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

