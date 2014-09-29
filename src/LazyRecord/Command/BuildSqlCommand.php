<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;
use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;
use LazyRecord\Command\BaseCommand;
use Exception;

class BuildSqlCommand extends BaseCommand
{

    public function options($opts)
    {
        parent::options($opts);

        // --rebuild
        $opts->add('r|rebuild','rebuild SQL schema.');

        // --clean
        $opts->add('c|clean','clean up SQL schema.');

        $opts->add('f|file:', 'write schema sql to file');

        $opts->add('b|basedata','insert basedata' );
    }

    public function usage()
    {
        return <<<DOC
lazy sql --data-source=mysql

lazy sql --data-source=master --rebuild

lazy sql --data-source=master --clean

DOC;
    }

    public function brief()
    {
        return 'build sql and insert into database.';
    }

    public function execute()
    {
        $options = $this->options;
        $logger  = $this->logger;

        CommandUtils::set_logger($this->logger);
        // XXX: from config files
        $id = $options->{'data-source'} ?: 'default';

        $logger->debug("Finding schema classes...");
        $schemas = CommandUtils::find_schemas_with_arguments( func_get_args() );

        $logger->debug("Initialize schema builder...");
        $sqlOutput = CommandUtils::build_schemas_with_options($id, $options, $schemas);
        if( $file = $this->options->file ) {
            $fp = fopen($file,'w');
            fwrite($fp, $sqlOutput);
            fclose($fp);
        }

        if( $this->options->basedata ) {
            CommandUtils::build_basedata($schemas);
        }

        $time = time();
        $logger->info("Setting migration timestamp to $time");
        $metadata = new Metadata($id);

        // update migration timestamp
        $metadata['migration'] = $time;

        $logger->info(
            $logger->formatter->format(
                'Done. ' . count($schemas) . " schema tables were generated into data source '$id'."
            ,'green')
        );
    }
}

