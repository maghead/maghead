<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\SqlBuilder;
use LazyRecord\BaseModel;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ClassUtils;
use LazyRecord\Result;
use PHPUnit_Framework_TestCase;
use CLIFramework\Logger;

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
        $config->setConfigStash(array( 'schema' => array( 'auto_id' => true ) ));
        $this->setConfig($config);

        $this->logger = new Logger;
        $this->logger->setQuiet();

        ob_start();
        $generator = new SchemaGenerator($this->config, $this->logger);
        $schemas = ClassUtils::schema_classes_to_objects( $this->getModels() );
        $classMap = $generator->generate($schemas);
        ob_end_clean();
    }

    public function getLogger()
    {
        return $this->logger;
    }


    public function getConfig()
    {
        return $this->config;
    }

    public function successfulDelete(BaseModel $record)
    {
        $this->assertResultSuccess($record->delete());
    }

    public function assertResultFail(Result $ret, $message = null) 
    {
        $this->assertTrue($ret->error, $message ?: $ret->message);
    }

    public function assertResultSuccess(Result $ret, $message = null) 
    {
        if ($ret->error === true) {
            // Pretty printing this
            var_dump( $ret );
        }
        $this->assertFalse($ret->error, $message ?: $ret->message);
    }

    public function resultOK($expect, Result $ret)
    {
        ok( $ret );
        if ($ret->success === $expect) {
            ok( $ret->success , $ret->message );
        }
        else {
            var_dump( $ret->sql ); 
            echo $ret->exception;
            ok( $ret->success );
        }
    }


}



