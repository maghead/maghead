<?php
namespace LazyRecord\Command\SchemaCommand;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\SchemaUtils;

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
        $classes = $this->findSchemasByArguments(func_get_args());
        $generator = new SchemaGenerator($config);
        if ($this->options->force) {
            $generator->setForceUpdate(true);
        }
        $classMap = $generator->generate($classes);
    }

}

