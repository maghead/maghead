<?php
namespace LazyRecord\Migration;
use LazyRecord\Console;
use LazyRecord\Metadata;
use LazyRecord\Schema\Comparator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\ConnectionManager;
use LazyRecord\Connection;
use LazyRecord\QueryDriver;
use LazyRecord\Migration\Migratable;
use GetOptionKit\OptionResult;

class AutomaticMigration extends Migration implements Migratable
{
    /*
    protected $driver;

    protected $connection;

    public function __construct(QueryDriver $driver, Connection $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }
    */

    protected $options = null;

    public function __construct($dsId, OptionResult $options = null) {
        parent::__construct($dsId);
        $this->options = $options ?: new OptionResult;
    }
    
    public function upgrade()
    {
        $parser = TableParser::create($this->driver, $this->connection);
        $tableSchemas = $parser->getTableSchemas();
        $comparator = new Comparator;

        // schema from runtime
        foreach ($schemas as $b) {
            $t = $b->getTable();
            $foundTable = isset($tableSchemas[$t]);
            if ($foundTable) {
                $a = $tableSchemas[ $t ]; // schema object, extracted from database.
                $diffs = $comparator->compare($a , $b);

                foreach ($diffs as $diff) {

                    switch($diff->flag) {
                    case '+':
                        // filter out useless columns
                        $columnArgs = array();
                        foreach ($diff->column->toArray() as $key => $value)
                        {
                            if (is_object($value) ||  is_array($value) ) {
                                continue;
                            }

                            // Supported attribute
                            if (in_array($key, ['type','primary','name','unique','default','notNull','null','autoIncrement'])) 
                            {
                                $columnArgs[ $key ] = $value;
                            }
                        }
                        $this->addColumn($t , $columnArgs);
                        break;
                    case '-':
                        if ($this->options->{'no-drop-column'}) {
                            continue;
                        }
                        $this->dropColumn($t, $diff->name);
                        break;
                    case '=':
                        if ($afterColumn = $diff->getAfterColumn()) {
                            $this->modifyColumn($t, $afterColumn);
                        } else {
                            throw new \Exception("afterColumn is undefined.");
                        }
                        break;
                    default:
                        $this->logger->warn("** unsupported flag: " . $diff->flag);
                        break;
                    }
                }
            } else {
                // generate create table statement.
                // use sqlbuilder to build schema sql
                $this->importSchema($b);
            }
        }
    }


}






