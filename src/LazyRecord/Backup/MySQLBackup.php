<?php
namespace LazyRecord\Backup;
use LazyRecord\Connection;
use Exception;

class MySQLBackup
{
    protected $mysqldump = 'mysqldump';

    protected $mysql = 'mysql';

    public function __construct() {

    }

    protected function formatCommandParameters(Connection $conn)
    {
        $dsn = $conn->getDSN();
        $config = $conn->getConfig();
        $parameters = [];
        if (isset($config['user'])) {
            $parameters[] = '-u' . $config['user'];
        }
        if (isset($config['pass'])) {
            $parameters[] = '-p' . $config['pass'];
        }
        if ($dbname = $dsn->getAttribute('dbname')) {
            $parameters[] = $dbname;
        } else {
            throw new Exception('dbname attribute is required.');
        }
        return $parameters;
    }

    /*
    $ mysqldbcopy \
        --source=root:pass@localhost:3310:/test123/mysql.sock \
        --destination=root:pass@localhost:3310:/test123/mysql.sock \
        util_test:util_test_copy

    $ mysqldump sourcedb -u <USERNAME> -p<PASS> | mysql destdb -u <USERNAME> -p<PASS>

    $socket = ini_get('pdo_mysql.default_socket')
        ?: ini_get('mysqli.default_socket')
        ?: ini_get('mysql.default_socket');


    */
    public function pipe(Connection $source, Connection $dest)
    {
        $dsn = $source->getDSN();
        $config = $source->getConfig();

        $dumpCommand = $this->mysqldump . ' ' . join(' ', $this->formatCommandParameters($source));
        $mysqlCommand = $this->mysql    . ' ' . join(' ', $this->formatCommandParameters($dest));

        $command = $dumpCommand . ' | ' . $mysqlCommand;
        $lastline = system($command, $ret);
        return $ret == 0 ? true : false;
    }
}




