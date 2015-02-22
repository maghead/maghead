<?php
namespace LazyRecord\Command;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\SchemaUtils;
use LazyRecord\Command\CommandUtils;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends BaseCommand
{

    public function usage()
    {
        return 'build-schema [paths|classes]';
    }

    public function brief()
    {
        return 'build schema files.';
    }

    public function arguments($args) {
        $args->add('file')
            ->isa('file')
            ;
    }

    public function options($opts) 
    {
        $opts->add('f|force','force generate all schema files.');
        parent::options($opts);
    }

    public function execute()
    {
        $logger = $this->getLogger();

        CommandUtils::set_logger($this->logger);

        $config = $this->getConfigLoader();

        $this->logger->debug('Finding schemas...');
        $classes = $this->findSchemasByArguments(func_get_args());

        SchemaUtils::printSchemaClasses($this->logger, $classes);

        $this->logger->debug("Initializing schema generator...");

        $generator = new SchemaGenerator($config, $this->logger);

        if ( $this->options->force ) {
            $generator->setForceUpdate(true);
        }

        $classMap = $generator->generate($classes);
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

