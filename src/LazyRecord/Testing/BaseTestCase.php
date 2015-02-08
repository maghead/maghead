<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\SqlBuilder;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ClassUtils;
use LazyRecord\Result;
use PHPUnit_Framework_TestCase;

abstract class BaseTestCase extends PHPUnit_Framework_TestCase
{
    public $driver = 'sqlite';
    public $config;


    public function getDSN()
    {
        if ($dsn = getenv('DB_' . strtoupper($this->driver) .  '_DSN')) {
            return $dsn;
        }
    }

    public function getDatabaseName() 
    {
        if ($name = getenv('DB_' . strtoupper($this->driver) .  '_NAME')) {
            return $name;
        }
    }

    public function getDatabaseUser() 
    {
        if ($user = getenv('DB_' . strtoupper($this->driver) . '_USER')) {
            return $user;
        }
    }

    public function getDatabasePassword() 
    {
        if ($pass = getenv('DB_' . strtoupper($this->driver) . '_PASS')) {
            return $pass;
        }
    }

    public function setConfig(ConfigLoader $config)
    {
        $this->config = $config;
    }

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('pdo extension is required for model testing');
            return;
        }

        // free and override default connection
        ConnectionManager::getInstance()->free();

        $config = ConfigLoader::getInstance();
        $config->loaded = true;
        $config->config = array( 'schema' => array( 'auto_id' => true ) );
        $this->setConfig($config);

        ob_start();
        $generator = new SchemaGenerator;
        $schemas = ClassUtils::schema_classes_to_objects( $this->getModels() );
        $classMap = $generator->generate($schemas);
        ob_end_clean();
    }

    public function getLogger()
    {
        return new \CLIFramework\Logger;
    }

    public function testClass()
    {
        foreach( $this->getModels() as $class ) 
            class_ok( $class );
    }

    public function assertResultSuccess(Result $ret) {
        if ($ret->error === true) {
            // Pretty printing this
            var_dump( $ret );
        }
        $this->assertFalse($ret->error, $ret->message);
    }

    public function resultOK($expect, Result$ret)
    {
        ok( $ret );
        if( $ret->success == $expect ) {
            ok( $ret->success , $ret->message );
        }
        else {
            var_dump( $ret->sql ); 
            echo $ret->exception;
            ok( $ret->success );
        }
    }
}



