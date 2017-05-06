<?php

namespace Maghead\Migration\Exception;

use PDO;
use Exception;
use Throwable;
use Maghead\Migration\BaseMigration;

class MigrationException extends Exception
{
    protected $sql;

    protected $migration;

    public function __construct($message, BaseMigration $migration, $sql, Throwable $e) {
        $this->migration = $migration;
        $this->sql = $sql;
        parent::__construct($message, 0, $e);
    }
}

