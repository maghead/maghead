<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Backup\MySQLBackup;
use Maghead\Connection;

class MySQLBackupTest extends ModelTestCase
{
    protected $onlyDriver = 'mysql';

    public function getModels()
    {
        return [];
    }

    public function testIncrementalBackup()
    {
        $backup = new MySQLBackup;

        $ds = $this->connManager->getDataSource($this->getMasterDataSourceId());
        if ($newdb = $backup->incrementalBackup($this->conn, $ds)) {
            $this->conn->query("DROP DATABASE IF EXISTS {$newdb}");
        }
    }

    public function testBackup()
    {
        $this->conn->query('DROP DATABASE IF EXISTS backup_test');
        $this->conn->query('CREATE DATABASE IF NOT EXISTS backup_test CHARSET utf8;');

        $source = [
            'driver' => 'mysql',
            'dsn' => 'mysql:host=localhost;dbname=backup_test',
            'user' => 'root',
            'pass' => null,
        ];
        $dest = [
            'driver' => 'mysql',
            'dsn' => 'mysql:host=localhost;dbname=mysql',
            'user' => 'root',
            'pass' => null,
        ];
        $backup = new MySQLBackup;
        $backup->backup($source, $dest);
        $this->conn->query('DROP DATABASE IF EXISTS backup_test');
    }
}
