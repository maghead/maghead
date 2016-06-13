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
use LazyRecord\Command\BaseCommand;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;
use LazyRecord\Schema\SchemaUtils;
use Exception;

class SqlCommand extends BaseCommand
{

    public function options($opts)
    {
        parent::options($opts);

        // --rebuild
        $opts->add('r|rebuild','rebuild SQL schema.');

        // --clean
        $opts->add('c|clean','clean up SQL schema.');

        $opts->add('o|output:', 'write schema sql to file');

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

        $id = $this->getCurrentDataSourceId();

        $logger->debug("Finding schema classes...");
        $schemas = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), func_get_args(), $this->logger);

        $logger->debug("Initialize schema builder...");

        if ($output = $this->options->output) {
            $configLoader = $this->getConfigLoader(true);
            $dataSourceConfig = $configLoader->getDataSource($id);
            $driverType = $dataSourceConfig['driver'];

            switch ($driverType) {
            case "sqlite":
                $driver = new SQLiteDriver;
                break;
            case "mysql":
                $driver = new MySQLDriver;
                break;
            case "pgsql":
                $driver = new PgSQLDriver;
                break;
            default:
                throw new Exception("Unsupported driver type: $driverType");
                break;
            }

            $sqlBuilder = SqlBuilder::create($driver,[
                'rebuild' => $options->rebuild,
                'clean' => $options->clean,
            ]);


            $fp = fopen($output, 'w');
            foreach ($schemas as $schema) {
                $sqls = $sqlBuilder->buildTable($schema);
                fwrite($fp, join("\n", $sqls));
                $sqls = $sqlBuilder->buildIndex($schema);
                fwrite($fp, join("\n", $sqls));
                $sqls = $sqlBuilder->buildForeignKeys($schema);
                fwrite($fp, join("\n", $sqls));
            }
            fclose($fp);


            $this->logger->warn('Warning: seeding is not supported when using --output option.');

        } else {

            $connectionManager = ConnectionManager::getInstance();
            $conn = $connectionManager->getConnection($id);
            $driver = $connectionManager->getQueryDriver($id);

            $sqlBuilder = SqlBuilder::create($driver,[
                'rebuild' => $options->rebuild,
                'clean' => $options->clean,
            ]);

            $builder = new DatabaseBuilder($conn, $sqlBuilder, $this->logger);
            $builder->build($schemas);

            if ($this->options->basedata) {
                $collection = new SchemaCollection($schemas);
                $collection = $collection->evaluate();
                $seedBuilder = new SeedBuilder($this->getConfigLoader(), $this->logger);
                $seedBuilder->build($collection);
            }

            $time = time();
            $logger->info("Setting migration timestamp to $time");
            $metadata = new Metadata($driver, $conn);

            // update migration timestamp
            $metadata['migration'] = $time;

            $logger->info(
                $logger->formatter->format(
                    'Done. ' . count($schemas) . " schema tables were generated into data source '$id'."
                ,'green')
            );
        }

    }
}

