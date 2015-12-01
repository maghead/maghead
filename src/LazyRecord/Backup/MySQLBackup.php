<?php

namespace LazyRecord\Backup;

use LazyRecord\Connection;
use Exception;
use LogicException;
use DateTime;

class MySQLBackup
{
    protected $mysqldump = 'mysqldump';

    protected $mysql = 'mysql';

    public function __construct()
    {
    }

    protected function formatCommandParameters(Connection $conn)
    {
        $dsn = $conn->getDSN();
        $config = $conn->getConfig();
        $parameters = [];
        if (isset($config['user'])) {
            $parameters[] = '-u'.$config['user'];
        }
        if (isset($config['pass'])) {
            $parameters[] = '-p'.$config['pass'];
        }
        if ($dbname = $dsn->getAttribute('dbname')) {
            $parameters[] = $dbname;
        } else {
            throw new Exception('dbname attribute is required.');
        }

        return $parameters;
    }

    public function incrementalBackup(Connection $source)
    {
        $newDSN = $source->getDSN();
        $dbname = $newDSN->getAttribute('dbname');
        $now = new DateTime();
        $dbname .= '_'.$now->format('Ymd_Hi');
        $source->query('CREATE DATABASE IF NOT EXISTS '.$dbname.' CHARSET utf8;');

        return $this->backupToDatabase($source, $dbname, false);
    }

    public function backupToDatabase(Connection $source, $databaseName, $dropAndCreate = false)
    {
        $newDSN = $source->getDSN();
        if ($newDSN->getAttribute('dbname') == $databaseName) {
            throw new LogicException('Backup to the same database.');
        }

        if ($dropAndCreate) {
            $source->query('DROP DATABASE IF EXISTS '.$databaseName);
            $source->query('CREATE DATABASE IF NOT EXISTS '.$databaseName.' CHARSET utf8;');
        }

        // Create new dest database connection
        $newConfig = $source->getConfig();
        $newDSN->setAttribute('dbname', $databaseName);
        $newConfig['dsn'] = $newDSN->__toString();
        $dest = Connection::create($newConfig);

        return $this->backup($source, $dest);
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
    public function backup(Connection $source, Connection $dest)
    {
        $dumpCommand = $this->mysqldump.' '.implode(' ', $this->formatCommandParameters($source));
        $mysqlCommand = $this->mysql.' '.implode(' ', $this->formatCommandParameters($dest));
        $command = $dumpCommand.' | '.$mysqlCommand;
        $lastline = system($command, $ret);

        return $ret == 0 ? true : false;
    }
}
