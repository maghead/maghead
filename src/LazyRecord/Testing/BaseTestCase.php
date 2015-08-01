<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\BaseModel;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\ClassUtils;
use LazyRecord\BaseCollection;
use LazyRecord\Result;
use LazyRecord\PDOExceptionPrinter;
use SQLBuilder\Driver\BaseDriver;
use PHPUnit_Framework_TestCase;
use CLIFramework\Logger;
use PDO;
use PDOException;
use Exception;

abstract class BaseTestCase extends PHPUnit_Framework_TestCase
{
    protected $config;

    static public function getCurrentDriverType()
    {
        return getenv('DB') ?: 'sqlite';
    }

    static public function getDSN($driver)
    {
        if ($dsn = getenv('DB_' . strtoupper($driver) .  '_DSN')) {
            return $dsn;
        }
    }

    static public function getDatabaseName($driver) 
    {
        if ($name = getenv('DB_' . strtoupper($driver) .  '_NAME')) {
            return $name;
        }
    }

    static public function getDatabaseUser($driver)
    {
        if ($user = getenv('DB_' . strtoupper($driver) . '_USER')) {
            return $user;
        }
    }

    static public function getDatabasePassword($driver) 
    {
        if ($pass = getenv('DB_' . strtoupper($driver) . '_PASS')) {
            return $pass;
        }
    }

    static public function createDataSourceConfig($driver) {
        if ($dsn = self::getDSN($driver)) {
            $config = array('dsn' => $dsn);
            $user = self::getDatabaseUser($driver);
            $pass = self::getDatabasePassword($driver);
            $config['user'] = $user;
            $config['pass'] = $pass;
            return $config;
        } else if ( self::getDatabaseName($driver) ) {
            return [
                'driver' => $driver,
                'database'  => self::getDatabaseName($driver),
                'user' => self::getDatabaseUser($driver),
                'pass' => self::getDatabasePassword($driver),
            ];
        } else {
            throw new Exception("Can't create data source config from $driver.");

        }
    }

    static public function createNeutralConfigLoader()
    {
        $config = ConfigLoader::getInstance();
        $config->loaded = true;
        $config->setConfigStash(array( 'schema' => array( 'auto_id' => true ) ));
        return $config;
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

        $config = self::createNeutralConfigLoader();
        $this->setConfig($config);

        $this->logger = new Logger;
        $this->logger->setQuiet();

        if (method_exists($this, 'getModels')) {
            $generator = new SchemaGenerator($this->config, $this->logger);
            $schemas = ClassUtils::schema_classes_to_objects($this->getModels());
            $classMap = $generator->generate($schemas);
        }
    }

    public function registerDataSource($driverType)
    {
        $connManager = ConnectionManager::getInstance();
        if ($dataSource = BaseTestCase::createDataSourceConfig($driverType)) {
            $connManager->addDataSource($driverType, $dataSource);
        } else {
            $this->markTestSkipped("Data source for $driverType is undefined");
        }
    }

    /**
     * @return array[] class map
     */
    public function updateSchemaFiles(DeclareSchema $schema)
    {
        $generator = new SchemaGenerator($this->config, $this->logger);
        return $generator->generate([$schema]);
    }


    public function buildSchemaTable(BaseDriver $driver, PDO $conn, DeclareSchema $schema, array $options = [ 'rebuild' => true ])
    {
        $builder = SqlBuilder::create($driver, $options);
        $this->assertNotNull($builder);

        $sqls = $builder->build($schema);
        foreach($sqls as $sql ) {
            $conn->query( $sql );
        }
    }


    public function matrixDataProvider(array $alist, array $blist)
    {
        $data = [];
        foreach($alist as $a) {
            foreach($blist as $b) {
                $data[] = [$a, $b];
            }
        }
        return $data;
    }

    public function driverTypeDataProvider()
    {
        $data = [];
        if (extension_loaded('pdo_mysql') ) {
            $data[] = ['mysql'];
        }
        if (extension_loaded('pdo_pgsql') ) {
            $data[] = ['pgsql'];
        }
        if (extension_loaded('pdo_sqlite') ) {
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
        switch($driverName) {
            case "mysql":
                $stm = $conn->query("SHOW COLUMNS FROM $tableName");
                break;
            case "pgsql":
                $stm = $conn->query("SELECT * FROM information_schema.columns WHERE table_name = '$tableName';");
                break;
            case "sqlite":
                $stm = $conn->query("select sql from sqlite_master where type = 'table' AND name = '$tableName'");
                break;
            default:
                throw new Exception("Unsupported PDO driver");
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
            PDOExceptionPrinter::show($e, $sql, $args, new Logger);
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
        $this->assertInstanceOf('LazyRecord\BaseModel', $record);
    }

    public function assertInstanceOfCollection(BaseCollection $collection)
    {
        $this->assertInstanceOf('LazyRecord\BaseCollection', $collection);
    }

    public function assertCollectionSize($size, BaseCollection $collection, $message = NULL)
    {
        $this->assertEquals($size, $collection->size(), $message ?: "Colletion size should match");
    }

    public function assertRecordLoaded(BaseModel $record, $message = NULL) 
    {
        $data = $record->getStashedData();
        $this->assertNotEmpty($data, $message ?: 'Record loaded');
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



