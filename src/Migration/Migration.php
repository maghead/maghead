<?php

namespace Maghead\Migration;

use SQLBuilder\Universal\Query\AlterTableQuery;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\MySQLDriver;
use Maghead\Schema\DynamicSchemaDeclare;
use CLIFramework\Logger;
use Exception;
use InvalidArgumentException;
use BadMethodCallException;

function buildColumn($arg)
{
    if (is_string($arg)) {
        return new Column($arg);
    } elseif (is_callable($arg)) {
        $column = new Column();
        if ($ret = call_user_func($arg, $column)) {
            return $ret;
        }

        return $column;
    } elseif ($arg instanceof Column) {
        return $arg;
    } else {
        throw new InvalidArgumentException('Invalid column argument');
    }
}

class Migration extends BaseMigration implements Upgradable, Downgradable
{
    public static function getId()
    {
        $name = get_called_class() ?: get_class($this);
        if (preg_match('#_(\d+)$#', $name, $regs)) {
            return $regs[1];
        }
        // throw new Exception("Can't parse migration script ID from class name: " . $name);
    }

    /**
     * Rename column requires $schema object.
     */
    public function renameColumn($table, $oldColumn, $newColumn)
    {
        if ($this->driver instanceof MySQLDriver && is_string($newColumn)) {
            throw new InvalidArgumentException('MySQLDriver requires the new column to be a column definition object.');
        }
        $query = new AlterTableQuery($table);
        $query->renameColumn($oldColumn, $newColumn);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function dropColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->dropColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function modifyColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->modifyColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    public function addColumn($table, $arg)
    {
        $column = buildColumn($arg);
        $query = new AlterTableQuery($table);
        $query->addColumn($column);
        $sql = $query->toSql($this->driver, new ArgumentArray());
        $this->query($sql);
    }

    /**
     * $this->createTable(function($s) {
     *      $s->column('title')->varchar(120);
     * });.
     */
    public function createTable($cb)
    {
        $ds = new DynamicSchemaDeclare();
        call_user_func($cb, $ds);
        $ds->build();

        $sqls = $this->builder->build($ds);
        $this->query($sqls);
    }

    public function upgrade()
    {
        $this->logger->info('Nothing to do');
    }

    public function downgrade()
    {
        $this->logger->info('Nothing to do');
    }

    public function __call($m, $a)
    {
        if (method_exists($this->builder, $m)) {
            $this->logger->info($m);
            $sql = call_user_func_array(array($this->builder, $m), $a);
            $this->query($sql);
        } else {
            throw new BadMethodCallException("Method $m does not exist.");
        }
    }
}
