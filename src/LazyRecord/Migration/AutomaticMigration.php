<?php
namespace LazyRecord\Migration;
use LazyRecord\Console;
use LazyRecord\Metadata;
use LazyRecord\Schema\Comparator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\ConnectionManager;
use LazyRecord\Connection;
use LazyRecord\QueryDriver;

class AutomaticMigration extends Migration
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
                $diffs = $comparator->compare( $a , $b );

                // generate alter table statement.
                foreach ($diffs as $diff) {
                    if ($diff->flag == '+') 
                    {
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
                        $this->addColumn( $t , $columnArgs );
                    }
                    else if ($diff->flag == '-') 
                    {
                        $this->dropColumn($t, $diff->name);
                    }
                    else if ($diff->flag == '=~')
                    {
                        throw new LogicException('Unimplemented');
                    }
                    else if ($diff->flag == '=') 
                    {
                        $this->logger->warn("** column flag = is not supported yet.");
                    }
                    else 
                    {
                        $this->logger->warn("** unsupported flag.");
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






