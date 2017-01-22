<?php

namespace Maghead\Command;

use CLIFramework\Logger;
use Maghead\Metadata;
use Maghead\Schema;
use Maghead\SqlBuilder\SqlBuilder;
use Maghead\Bootstrap;
use Maghead\ConnectionManager;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;
use Maghead\Schema\SchemaUtils;
use Exception;

class SqlCommand extends BaseCommand
{
    public function options($opts)
    {
        parent::options($opts);

        // --rebuild
        $opts->add('r|rebuild', 'rebuild SQL schema.');

        // --clean
        $opts->add('c|clean', 'clean up SQL schema.');

        $opts->add('o|output:', 'write schema sql to file');

        $opts->add('b|basedata', 'insert basedata');
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
        $logger = $this->logger;
        $configLoader = $this->getConfigLoader(true);

        $id = $this->getCurrentDataSourceId();

        $logger->debug('Finding schema classes...');
        $schemas = SchemaUtils::findSchemasByArguments($configLoader, func_get_args(), $this->logger);

        $logger->debug('Initialize schema builder...');

        if ($output = $this->options->output) {
            $dataSourceConfig = $configLoader->getDataSource($id);
            $driverType = $dataSourceConfig['driver'];

            switch ($driverType) {
            case 'sqlite':
                $driver = new SQLiteDriver();
                break;
            case 'mysql':
                $driver = new MySQLDriver();
                break;
            case 'pgsql':
                $driver = new PgSQLDriver();
                break;
            default:
                throw new Exception("Unsupported driver type: $driverType");
                break;
            }

            $sqlBuilder = SqlBuilder::create($driver, [
                'rebuild' => $options->rebuild,
                'clean' => $options->clean,
            ]);

            $fp = fopen($output, 'w');
            foreach ($schemas as $schema) {
                $sqls = $sqlBuilder->buildTable($schema);
                fwrite($fp, implode("\n", $sqls));
                $sqls = $sqlBuilder->buildIndex($schema);
                fwrite($fp, implode("\n", $sqls));
                $sqls = $sqlBuilder->buildForeignKeys($schema);
                fwrite($fp, implode("\n", $sqls));
            }
            fclose($fp);

            $this->logger->warn('Warning: seeding is not supported when using --output option.');
        } else {
            $connectionManager = ConnectionManager::getInstance();
            $conn = $connectionManager->getConnection($id);
            $driver = $connectionManager->getQueryDriver($id);

            $sqlBuilder = SqlBuilder::create($driver, [
                'rebuild' => $options->rebuild,
                'clean' => $options->clean,
            ]);

            $bootstrap = new Bootstrap($conn, $sqlBuilder, $this->logger);
            $bootstrap->build($schemas);
            if ($this->options->basedata) {
                $bootstrap->seed($schemas, $configLoader);
            }

            $time = time();
            $logger->info("Setting migration timestamp to $time");
            $metadata = new Metadata($conn, $driver);

            // update migration timestamp
            $metadata['migration'] = $time;

            $logger->info(
                $logger->formatter->format(
                    'Done. '.count($schemas)." schema tables were generated into data source '$id'.", 'green')
            );
        }
    }
}
