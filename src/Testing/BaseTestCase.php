<?php

namespace Maghead\Testing;

use Maghead\ConnectionManager;
use Maghead\SqlBuilder\SqlBuilder;
use Maghead\BaseModel;
use Maghead\ConfigLoader;
use Maghead\Schema\SchemaGenerator;
use Maghead\Schema\DeclareSchema;
use Maghead\BaseCollection;
use Maghead\Result;
use Maghead\PDOExceptionPrinter;
use SQLBuilder\Driver\BaseDriver;
use PHPUnit_Framework_TestCase;
use CLIFramework\Logger;
use PDO;
use PDOException;
use Exception;

abstract class BaseTestCase extends PHPUnit_Framework_TestCase
{
    public $driver = 'sqlite';

    public $dataSource;

    public $onlyDriver;

    protected $connManager;

    protected $config;

    /**
     * @var Maghead\Connection
     */
    protected $conn;

    protected $queryDriver;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if (!extension_loaded('pdo')) {
            return $this->markTestSkipped('pdo extension is required for model testing');
        }

        // The config loader is used to initialize connection manager
        $this->config = ConfigLoader::getInstance();
        $this->config->loadFromSymbol(true);
        $this->config->setDefaultDataSourceId($this->getDataSource());

        // Always true
        $configStash = $this->config->getConfigStash();
        $cnofigStash['schema']['auto_id'] = true;

        // free and override default connection
        $this->connManager = ConnectionManager::getInstance();
        $this->connManager->init($this->config);

        // $config = self::createNeutralConfigLoader();
        $this->logger = new Logger();
        $this->logger->setQuiet();
    }

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->getDataSource() != $this->onlyDriver) {
            return $this->markTestSkipped("{$this->onlyDriver} only");
        }
        $this->prepareConnection();
    }

    protected function prepareConnection()
    {
        if (!$this->conn) {
            try {
                $this->conn = $this->connManager->getConnection($this->getDataSource());
            } catch (PDOException $e) {
                if ($this->allowConnectionFailure) {
                    $this->markTestSkipped(
                        sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'",
                            $this->getDataSource(),
                            $e->getMessage(),
                            var_export($this->config->getDataSource($this->getDataSource()), true)
                        ));

                    return;
                }
                echo sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'",
                    $this->getDataSource(),
                    $e->getMessage(),
                    var_export($this->config->getDataSource($this->getDataSource()), true)
                );
                throw $e;
            }
            $this->queryDriver = $this->connManager->getQueryDriver($this->getDataSource());
        }
    }

    public function getDataSource()
    {
        if ($this->dataSource) {
            return $this->dataSource;
        }

        return $this->getDriverType();
    }

    public function getDriverType()
    {
        return getenv('DB') ?: $this->driver;
    }

    public static function getDSN($driver)
    {
        if ($dsn = getenv('DB_'.strtoupper($driver).'_DSN')) {
            return $dsn;
        }
    }

    public static function getDatabaseName($driver)
    {
        if ($name = getenv('DB_'.strtoupper($driver).'_NAME')) {
            return $name;
        }
    }

    public static function getDatabaseUser($driver)
    {
        if ($user = getenv('DB_'.strtoupper($driver).'_USER')) {
            return $user;
        }
    }

    public static function getDatabasePassword($driver)
    {
        if ($pass = getenv('DB_'.strtoupper($driver).'_PASS')) {
            return $pass;
        }
    }

    public static function createDataSourceConfig($driver)
    {
        if ($dsn = self::getDSN($driver)) {
            $config = array('dsn' => $dsn);
            $user = self::getDatabaseUser($driver);
            $pass = self::getDatabasePassword($driver);
            $config['user'] = $user;
            $config['pass'] = $pass;

            return $config;
        } elseif (self::getDatabaseName($driver)) {
            return [
                'driver' => $driver,
                'database' => self::getDatabaseName($driver),
                'user' => self::getDatabaseUser($driver),
                'pass' => self::getDatabasePassword($driver),
            ];
        } else {
            throw new Exception("Can't create data source config from $driver.");
        }
    }

    public static function createNeutralConfigLoader()
    {
        $config = ConfigLoader::getInstance();
        $config->loaded = true;
        $config->setConfigStash(array('schema' => array('auto_id' => true)));

        return $config;
    }

    public function setConfig(ConfigLoader $config)
    {
        $this->config = $config;
    }

    protected function registerDataSource($driverType)
    {
        if ($dataSource = self::createDataSourceConfig($driverType)) {
            $this->connManager->addDataSource($driverType, $dataSource);
        } else {
            $this->markTestSkipped("Data source for $driverType is undefined");
        }
    }

    /**
     * @return array[] class map
     */
    protected function updateSchemaFiles(DeclareSchema $schema)
    {
        $generator = new SchemaGenerator($this->config, $this->logger);

        return $generator->generate([$schema]);
    }

    protected function buildSchemaTable(PDO $conn, BaseDriver $driver, DeclareSchema $schema, array $options = ['rebuild' => true])
    {
        $builder = SqlBuilder::create($driver, $options);
        $sqls = array_filter(array_merge($builder->prepare(), $builder->build($schema), $builder->finalize()));
        foreach ($sqls as $sql) {
            $conn->query($sql);
        }
    }

    public function matrixDataProvider(array $alist, array $blist)
    {
        $data = [];
        foreach ($alist as $a) {
            foreach ($blist as $b) {
                $data[] = [$a, $b];
            }
        }

        return $data;
    }

    public function driverTypeDataProvider()
    {
        $data = [];
        if (extension_loaded('pdo_mysql')) {
            $data[] = ['mysql'];
        }
        if (extension_loaded('pdo_pgsql')) {
            $data[] = ['pgsql'];
        }
        if (extension_loaded('pdo_sqlite')) {
            $data[] = ['sqlite'];
        }

        return $data;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function assertTableExists(PDO $conn, $tableName)
    {
        $driverName = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driverName) {
            case 'mysql':
                $stm = $conn->query("SHOW COLUMNS FROM $tableName");
                break;
            case 'pgsql':
                $stm = $conn->query("SELECT * FROM information_schema.columns WHERE table_name = '$tableName';");
                break;
            case 'sqlite':
                $stm = $conn->query("select sql from sqlite_master where type = 'table' AND name = '$tableName'");
                break;
            default:
                throw new Exception('Unsupported PDO driver');
                break;
        }
        $result = $stm->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($result);
    }

    public function assertQueryOK(PDO $conn, $sql, $args = array())
    {
        try {
            $ret = $conn->query($sql);
            $this->assertNotNull($ret);

            return $ret;
        } catch (PDOException $e) {
            PDOExceptionPrinter::show($e, $sql, $args, new Logger());
            throw $e;
        }
    }

    public function successfulDelete(BaseModel $record)
    {
        $this->assertResultSuccess($record->delete());
    }

    public function assertResultFail(Result $ret, $message = null)
    {
        $this->assertTrue($ret->error, $message ?: $ret->message);
    }

    public function assertInstanceOfModel(BaseModel $record)
    {
        $this->assertInstanceOf('Maghead\BaseModel', $record);
    }

    public function assertInstanceOfCollection(BaseCollection $collection)
    {
        $this->assertInstanceOf('Maghead\BaseCollection', $collection);
    }

    public function assertCollectionSize($size, BaseCollection $collection, $message = null)
    {
        $this->assertEquals($size, $collection->size(), $message ?: 'Colletion size should match');
    }

    public function assertRecordLoaded(BaseModel $record, $message = null)
    {
        $data = $record->getStashedData();
        $this->assertNotEmpty($data, $message ?: 'Record loaded');
    }

    public function assertResultSuccess(Result $ret, $message = null)
    {
        if ($ret->error === true) {
            // Pretty printing this
            var_dump($ret);
        }
        $this->assertFalse($ret->error, $message ?: $ret->message);
    }

    public function resultOK($expect, Result $ret)
    {
        $this->assertNotNull($ret);
        if ($ret->success === $expect) {
            ok($ret->success, $ret->message);
        } else {
            var_dump($ret->sql);
            echo $ret->exception;
            ok($ret->success);
        }
    }
}
