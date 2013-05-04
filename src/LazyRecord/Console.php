<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'LazyRecord';
    const VERSION = "1.9.24";

    public function brief()
    {
        return 'LazyRecord ORM';
    }

    public function init()
    {
        parent::init();

        /**
         * command for initialize related file structure
         */
        $this->registerCommand('init',    'LazyRecord\Command\InitCommand');

        /**
         * command for building config file.
         */
        $this->registerCommand('build-conf',   'LazyRecord\Command\BuildConfCommand');

        /**
         * schema command.
         */
        $this->registerCOmmand('schema',  'LazyRecord\Command\SchemaCommand');
        $this->registerCOmmand('list-schema',  'LazyRecord\Command\ListSchemaCommand');
        $this->registerCommand('build-schema', 'LazyRecord\Command\BuildSchemaCommand');

        $this->registerCommand('build-basedata', 'LazyRecord\Command\BuildBaseDataCommand');

        $this->registerCommand('build-sql',    'LazyRecord\Command\BuildSqlCommand');

        $this->registerCommand('diff',         'LazyRecord\Command\DiffCommand');

        $this->registerCommand('migrate',         'LazyRecord\Command\MigrateCommand');

        $this->registerCommand('prepare',         'LazyRecord\Command\PrepareCommand');

        $this->registerCommand('metadata',         'LazyRecord\Command\MetadataCommand');

        $this->registerCommand('create-db',         'LazyRecord\Command\CreateDBCommand');
    }

    public static function getInstance() 
    {
        static $self;
        if( $self )
            return $self;
        return $self = new self;
    }

}
