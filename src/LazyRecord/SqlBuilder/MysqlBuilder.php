<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;

class MysqlBuilder
    extends BaseBuilder
    implements BuilderInterface
{

    public function buildColumnSql($schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->driver->getQuoteColumn( $name );
        $sql .= ' ' . $type;

        if ( $isa === 'enum' && !empty($column->enum) ) {
            $enum = array();
            foreach ($column->enum as $val) {
                $enum[] = $this->driver->inflate($val);
            }
            $sql .= '(' . implode(', ', $enum) . ')';
        }

        if( $column->required || $column->notNull )
            $sql .= ' NOT NULL';
        elseif( $column->null )
            $sql .= ' NULL';

        /* if it's callable, we should not write the result into sql schema */
        if( ($default = $column->default) !== null && ! is_callable($column->default )  ) { 

            // raw sql default value
            if( is_array($default) ) {
                $sql .= ' default ' . $default[0];
            }
            else {
                $sql .= ' default ' . $this->driver->inflate($default);
            }
        }

        if( $column->comment )
            $sql .= ' comment ' . $this->driver->inflate($column->comment);

        if( $column->primary )
            $sql .= ' primary key';

        if( $column->autoIncrement )
            $sql .= ' auto_increment';

        if( $column->unique )
            $sql .= ' unique';

        /**
        build reference

        track(
        	FOREIGN KEY(trackartist) REFERENCES artist(artistid)
            artist_id INTEGER REFERENCES artist
        )

        MySQL Syntax:
        
            reference_definition:

            REFERENCES tbl_name (index_col_name,...)
                [MATCH FULL | MATCH PARTIAL | MATCH SIMPLE]
                [ON DELETE reference_option]
                [ON UPDATE reference_option]

            reference_option:
                RESTRICT | CASCADE | SET NULL | NO ACTION

        A reference example:

        PRIMARY KEY (`idEmployee`) ,
        CONSTRAINT `fkEmployee_Addresses`
        FOREIGN KEY `fkEmployee_Addresses` (`idAddresses`)
        REFERENCES `schema`.`Addresses` (`idAddresses`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION

        */
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( $name != 'id' && $rel['self_column'] == $name ) 
                {
                    $fSchema = new $rel['foreign_schema'];
                    $fColumn = $rel['foreign_column'];
                    $fc = $fSchema->columns[$fColumn];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                }
                break;
            }
        }

        return $sql;
    }


    public function dropTable($schema)
    {
        return 'DROP TABLE IF EXISTS ' 
            . $this->driver->getQuoteTableName( $schema->getTable() )
            . ';';
    }


    public function buildIndex($schema) 
    {
        $sqls = array();
        foreach( $schema->columns as $name => $column ) {
            if ( $column->index ) {
                $indexName = is_string($column->index) ? $column->index 
                    : "idx_" . $name;
                $builder = new IndexBuilder($this->driver);
                $builder->create( $indexName )
                    ->on( $schema->getTable() )
                    ->columns($name)
                    ;
                $sqls[] = $builder->build();
            }
        }
        return $sqls;
    }

}

