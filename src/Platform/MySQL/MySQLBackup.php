<?php

namespace Maghead\Platform\MySQL;

use Maghead\Runtime\Connection;
use Maghead\DSN\DSNParser;
use LogicException;
use DateTime;

class MySQLBackup
{
    protected $mysqldump;

    protected $mysql;

    public function __construct($mysql = 'mysql', $mysqldump = 'mysqldump')
    {
        $this->mysql = $mysql;
        $this->mysqldump = $mysqldump;
    }

    /**
     * Given a config array, return the command line parameters
     */
    protected function formatCommandParameters(array $config)
    {
        $dsn = DSNParser::parse($config['dsn']);
        $parameters = ['--default-character-set=utf8'];
        if (isset($config['user'])) {
            $parameters[] = '-u'.$config['user'];
        }
        if (isset($config['pass'])) {
            $parameters[] = '-p'.$config['pass'];
        }
        if ($dbname = $dsn->getAttribute('dbname')) {
            $parameters[] = $dbname;
        }
        return implode(' ', $parameters);
    }

    /**
     * Get the DSN from the current connection object,
     * alternate the dbname,
     * and then create a new connection base on new DSN.
     */
    public function incrementalBackup(Connection $conn, array $source)
    {
        $dest = $source;
        $destDSN = DSNParser::parse($dest['dsn']);

        $newdb = $destDSN->getAttribute('dbname').'_'.date('Ymd_Hi');
        $destDSN['dbname'] = $newdb;
        $dest['dsn'] = $destDSN->__toString();

        $conn->query("CREATE DATABASE IF NOT EXISTS {$newdb} CHARSET utf8");
        if ($this->backup($source, $dest)) {
            return $newdb;
        }
        return false;
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
    public function backup(array $source, array $dest)
    {
        $dumpCommand = $this->mysqldump.' '.$this->formatCommandParameters($source);
        $mysqlCommand = $this->mysql.' '.$this->formatCommandParameters($dest);
        $command = $dumpCommand.' | '.$mysqlCommand;
        $lastline = system($command, $ret);

        return $ret == 0 ? true : false;
    }
}
