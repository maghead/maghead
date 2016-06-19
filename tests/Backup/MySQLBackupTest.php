<?php
use LazyRecord\Testing\ModelTestCase;
use LazyRecord\Backup\MySQLBackup;
use LazyRecord\ConnectionManager;
use LazyRecord\Connection;

class MySQLBackupTest extends ModelTestCase
{
    public $driver = 'mysql';

    public function getModels()
    {
        return [];
    }

    public function testIncrementalBackup()
    {
        $connManager = ConnectionManager::getInstance();
        $source = $connManager->getConnection('mysql');
        $backup = new MySQLBackup;
        $backup->incrementalBackup($source);
    }

    public function testBackupToDatabase()
    {
        $connManager = ConnectionManager::getInstance();
        $source = $connManager->getConnection('mysql');
        $backup = new MySQLBackup;
        $backup->backupToDatabase($source, 'backup_test2', true);
    }

    public function testBackup()
    {
        $connManager = ConnectionManager::getInstance();
        $source = $connManager->getConnection('mysql');
        $source->query('DROP DATABASE IF EXISTS backup_test');
        $source->query('CREATE DATABASE IF NOT EXISTS backup_test CHARSET utf8;');
        $dest = Connection::create([
            'dsn' => 'mysql:host=localhost;dbname=backup_test',
            'user' => 'root',
            'pass' => null,
            'connection_options' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ]
        ]);
        $backup = new MySQLBackup;
        $backup->backup($source, $dest);
    }
}
