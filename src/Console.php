<?php

namespace Maghead;

use CLIFramework\Application;

class Console extends Application
{
    const name = 'Maghead';
    const VERSION = '2.2.4';

    public function brief()
    {
        return 'Maghead ORM';
    }

    public function getServiceContainer()
    {
        return new ServiceContainer();
    }

    public function init()
    {
        parent::init();

        /*
         * Command for initialize related file structure
         */
        $this->command('init');

        /*
         * Command for building config file.
         */
        $this->command('build-conf', 'Maghead\\Command\\BuildConfCommand');
        $this->command('conf',       'Maghead\\Command\\BuildConfCommand');
        $this->command('init-conf',  'Maghead\\Command\\InitConfCommand');

        $this->command('schema'); // the schema command builds all schema files and shows a diff after building new schema
        $this->command('basedata');
        $this->command('sql');
        $this->command('diff');
        $this->command('migrate');
        $this->command('meta');
        $this->command('version');
        $this->command('db');
        $this->command('data-source');
        $this->command('table');
        $this->command('index');
    }

    public static function getInstance()
    {
        static $self;
        if ($self) {
            return $self;
        }

        return $self = new self();
    }
}
