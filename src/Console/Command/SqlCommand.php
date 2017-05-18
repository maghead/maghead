<?php

namespace Maghead\Console\Command;

use CLIFramework\Logger;
use Maghead\Schema\DeclareSchema;
use Maghead\TableBuilder\TableBuilder;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\TableManager;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaCollection;
use Maghead\Runtime\SeedBuilder;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Driver\SQLiteDriver;
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

    public function execute($nodeId = 'master')
    {
        $config = $this->getConfig();

        $this->logger->debug('Finding schema classes...');
        $schemas = $this->loadSchemasFromArguments(func_get_args());

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
                'rebuild' => $this->options->rebuild,
                'clean' => $this->options->clean,
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
            $conn = $this->dataSourceManager->getConnection($nodeId);
            $driver = $conn->getQueryDriver();

            $tableManager = new TableManager($conn, [
                'rebuild' => $this->options->rebuild,
                'clean' => $this->options->clean,
            ], $this->logger);

            $tableManager->build($schemas);

            if ($this->options->basedata) {
                $seedBuilder = new SeedBuilder($this->logger);
                $seedBuilder->build(new SchemaCollection($schemas));
                if ($seeds = $config->loadSeedScripts()) {
                    $seedBuilder->buildSeeds($seeds);
                }
            }

            $time = time();
            $this->logger->info("Setting migration timestamp to $time");
            $metadata = new MetadataManager($conn, $driver);

            // update migration timestamp
            $metadata['migration'] = $time;

            $this->logger->info(
                $this->logger->formatter->format(
                    'Done. '.count($schemas)." schema tables were generated into data source '$nodeId'.", 'green')
            );
        }
    }
}
