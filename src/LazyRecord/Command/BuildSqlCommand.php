<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;
use Exception;

class BuildSqlCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        // --rebuild
        $opts->add('rebuild','rebuild SQL schema.');

        // --clean
        $opts->add('clean','clean up SQL schema.');

        $opts->add('f|file:', 'write schema sql to file');

        $opts->add('basedata','insert basedata' );

        // --data-source
        $opts->add('D|data-source:', 'specify data source id');
    }

    public function usage()
    {
        return <<<DOC
lazy build-sql --data-source=mysql

lazy build-sql --data-source=master --rebuild

lazy build-sql --data-source=master --clean

DOC;
    }

    public function brief()
    {
        return 'build sql and insert into database.';
    }

    public function execute()
    {
        // support for schema file or schema class names
        $schemas = func_get_args();

        $options = $this->options;
        $logger  = $this->logger;

        CommandUtils::init_config_loader();


        // XXX: from config files
        $id = $options->{'data-source'} ?: 'default';

        $logger->info("Finding schema classes...");

        CommandUtils::set_logger($this->logger);
        $classes = CommandUtils::find_schemas_with_arguments( func_get_args() );

        $logger->info('Found schema classes');
        foreach( $classes as $class ) {
            $logger->info( $logger->formatter->format($class,'green') ,1 );
        }

        $schemas = CommandUtils::schema_classes_to_objects( $classes );

        $logger->info("Connecting to data soruce $id...");

        $logger->info("Initialize schema builder...");
        $sqlOutput = CommandUtils::build_schemas_with_options($id, $options, $schemas);
        if( $file = $this->options->file ) {
            $fp = fopen($file,'w');
            fwrite($fp, $sqlOutput);
            fclose($fp);
        }

        if( $this->options->basedata ) {
            CommandUtils::build_basedata($schemas);
        }

        $logger->info('Schema SQL is generated, please check schema.sql file.');
    }
}

