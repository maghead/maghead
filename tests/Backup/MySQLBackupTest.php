<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Backup\MySQLBackup;
use Maghead\ConnectionManager;
use Maghead\Connection;

class MySQLBackupTest extends ModelTestCase
{
    public $onlyDriver = 'mysql';

    public function getModels()
    {
        return [];
    }

    public function testIncrementalBackup()
    {
        $backup = new MySQLBackup;
        if ($createdDB = $backup->incrementalBackup($this->conn)) {
            // FIXME:
            $this->conn->query("DROP DATABASE IF EXISTS $createdDB");
        }
    }

    public function testBackupToDatabase()
    {
        $backup = new MySQLBackup;
        $backup->backupToDatabase($this->conn, 'backup_test2', true);
    }

    public function testBackup()
    {
        $this->conn->query('DROP DATABASE IF EXISTS backup_test');
        $this->conn->query('CREATE DATABASE IF NOT EXISTS backup_test CHARSET utf8;');
        $dest = Connection::create([
            'dsn' => 'mysql:host=localhost;dbname=backup_test',
            'user' => 'root',
            'pass' => null,
            'connection_options' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ]
        ]);
        $backup = new MySQLBackup;
        $backup->backup($this->conn, $dest);
    }
}
