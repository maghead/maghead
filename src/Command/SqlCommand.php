<?php

namespace Maghead\Command;

use CLIFramework\Logger;
use Maghead\Schema;
use Maghead\TableBuilder\TableBuilder;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\TableManager;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaCollection;
use Maghead\SeedBuilder;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;
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
maghead sql master

maghead sql --rebuild master

maghead sql --clean master

DOC;
    }

    public function brief()
    {
        return 'build sql and insert into database.';
    }

    public function execute($nodeId)
    {
        $options = $this->options;
        $logger = $this->logger;
        $config = $this->getConfig();

        $this->logger->debug('Finding schema classes...');
        $schemas = $this->findSchemasByArguments(func_get_args());

        $this->logger->debug('Initialize schema builder...');

        if ($output = $this->options->output) {
            $dataSourceConfig = $config->getDataSource($nodeId);
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

            $sqlBuilder = TableBuilder::create($driver, [
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
            $dataSourceManager = DataSourceManager::getInstance();
            $conn = $dataSourceManager->getConnection($nodeId);
            $driver = $dataSourceManager->getQueryDriver($nodeId);

            $tableManager = new TableManager($conn, [
                'rebuild' => $options->rebuild,
                'clean' => $options->clean,
            ], $this->logger);

            $tableManager->build($schemas);

            if ($this->options->basedata) {
                $seedBuilder = new SeedBuilder($this->logger);
                $seedBuilder->build(new SchemaCollection($schemas));
                if ($config) {
                    $seedBuilder->buildConfigSeeds($config);
                }
            }

            $time = time();
            $logger->info("Setting migration timestamp to $time");
            $metadata = new MetadataManager($conn, $driver);

            // update migration timestamp
            $metadata['migration'] = $time;

            $logger->info(
                $logger->formatter->format(
                    'Done. '.count($schemas)." schema tables were generated into data source '$nodeId'.", 'green')
            );
        }
    }
}
