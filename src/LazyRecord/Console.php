<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'LazyRecord';
    const VERSION = "2.2.4";

    public function brief()
    {
        return 'LazyRecord ORM';
    }

    public function init()
    {
        parent::init();

        /**
         * Command for initialize related file structure
         */
        $this->command('init');

        /**
         * Command for building config file.
         */
        $this->command('build-conf', 'LazyRecord\\Command\\BuildConfCommand');
        $this->command('conf',       'LazyRecord\\Command\\BuildConfCommand');

        $this->command('schema'); // the schema command builds all schema files and shows a diff after building new schema
        $this->command('basedata');
        $this->command('sql');
        $this->command('diff');
        $this->command('migrate');
        $this->command('meta');
        $this->command('version');
        $this->command('db');
        $this->command('data-source');
    }

    public static function getInstance() 
    {
        static $self;
        if( $self )
            return $self;
        return $self = new self;
    }
}
