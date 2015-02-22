<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use CLIFramework\Logger;
use LazyRecord\Metadata;
use LazyRecord\Schema;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\SeedBuilder;
use LazyRecord\DatabaseBuilder;
use LazyRecord\Schema\SchemaCollection;
use LazyRecord\ConfigLoader;
use LazyRecord\ConnectionManager;
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
        $id = $this->getCurrentDataSourceId();

        $logger->debug("Finding schema classes...");
        $schemas = $this->findSchemasByArguments(func_get_args());

        $logger->debug("Initialize schema builder...");
        // $sqlOutput = CommandUtils::build_schemas_with_options($id, $options, $schemas);


        $connectionManager = ConnectionManager::getInstance();
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);
        $sqlBuilder = SqlBuilder::create($driver, array( 
            'rebuild' => $options->rebuild,
            'clean' => $options->clean,
        ));

        $builder = new DatabaseBuilder($conn, $sqlBuilder, new Logger);
        $sqls    = $builder->build($schemas);
        $sqlOutput = join("\n", $sqls );


        if ($file = $this->options->file) {
            $fp = fopen($file,'w');
            fwrite($fp, $sqlOutput);
            fclose($fp);
        }

        if ($this->options->basedata) {
            // CommandUtils::build_basedata($schemas);
            $collection = new SchemaCollection($schemas);
            $collection = $collection->evaluate();

            $seedBuilder = new SeedBuilder($this->getConfigLoader(), $this->logger);
            $seedBuilder->build($collection);
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

