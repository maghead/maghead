<?php
namespace LazyRecord\SchemaSqlBuilder;
use LazyRecord\SchemaDeclare;

class SqliteDriver
	implements DriverInterface
{


    function buildColumnSql($column) {      
		$name = $column->name;
		$isa  = $column->isa ?: 'str';
		$type = $column->type;
		if( ! $type && $isa == 'str' )
			$type = 'text';

		$sql  = $name;
		$sql .= ' ' . $type;

        if( $column->required )
            $sql .= " not null";

		/* if it's callable, we should not write the result into sql schema */
		if( ($default = $column->default) !== null && ! is_callable($column->default )  ) { 

			if( is_string($default) ) {
				$sql .= " default '" . addslashes($default) . "'";
			}
			elseif( is_numeric($default) ) { 
				$sql .= " default $default";
			}
			elseif( is_bool($default) ) {
				$sql .= ' default ' . ( $default === true ? 'TRUE' : 'FALSE' );
			}
			elseif( is_array($default) ) {
				// raw sql default value
				$sql .= " default " . $default[0];
			}
			else {
				$sql .= " default $default";
			}
		}

        if( $column->primary )
            $sql .= ' primary key';
        
        if( $column->autoIncrement )
            $sql .= ' autoincrement';

		if( $column->unique )
			$sql .= ' unique';

        return $sql;
    }


	public function build(SchemaDeclare $schema)
	{

        $sql = "CREATE TABLE " . $schema->getTable() . "( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
			$columnSql[] = $this->buildColumnSql( $column );
        }
        $sql .= join(",\n",$columnSql);
        $sql .= "\n);\n";
        return $sql;
	}

}

