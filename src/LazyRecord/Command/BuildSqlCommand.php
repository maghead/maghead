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

        $logger->info("Initialize schema builder...");


        $schemas = CommandUtils::schema_classes_to_objects( $classes );


        $logger->info("Initialize connection manager...");
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();

        $logger->info("Connecting to data soruce $id...");
        $conn = $connectionManager->getConnection($id);
        $driver = $connectionManager->getQueryDriver($id);
        $builder = \LazyRecord\SqlBuilder\SqlBuilderFactory::create($driver, array( 
            'rebuild' => $options->rebuild,
            'clean' => $options->clean,
        )); // driver
        $fp = fopen('schema.sql','w'); // write only
        foreach( $schemas as $schema ) {
            $class = get_class($schema);
            $logger->info( $logger->formatter->format("Building SQL for " . $class,'green') );

            fwrite( $fp , "--- Schema $class\n" );

            $sqls = $builder->build($schema);
            foreach( $sqls as $sql ) {
                $logger->info("--- SQL for schema $class ");
                $logger->info( $sql );
                fwrite( $fp , $sql . "\n" );

                $conn->query( $sql );
                $error = $conn->errorInfo();
                if( $error[1] ) {
                    $msg =  $class . ': ' . var_export( $error , true );
                    $logger->error($msg);
                    fwrite( $fp , $msg);
                }
            }
        }

        if( $this->options->basedata ) {
            CommandUtils::create_basedata($schemas);
        }

        $logger->info('Schema SQL is generated, please check schema.sql file.');
        fclose($fp);
    }
}

