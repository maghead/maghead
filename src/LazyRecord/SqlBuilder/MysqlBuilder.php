<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\DeclareColumn;
use SQLBuilder\ArgumentArray;

class MysqlBuilder extends BaseBuilder
{
    public function buildColumnSql(SchemaInterface $schema, DeclareColumn $column)
    {
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        if (! $column->type && $isa == 'str' ) {
            $column->type = 'text';
        }

        $args = new ArgumentArray;
        $sql = $column->buildDefinitionSql($this->driver, $args);

        /**
        BUILD COLUMN REFERENCE

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


        FOREIGN KEY (`order_uuid`) REFERENCES orders(`uuid`)


        A column with foreign key should not be nullable.
        @see http://stackoverflow.com/questions/10028214/add-foreign-key-to-existing-table
        */
        foreach ($schema->relations as $rel) {
            switch( $rel['type'] ) {
            case Relationship::BELONGS_TO:
            // case Relationship::HAS_MANY:
            // case Relationship::HAS_ONE:
                if ($name != 'id' && $rel['self_column'] == $name) {
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


    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
            . $this->driver->quoteIdentifier( $schema->getTable() )
            . ';';
    }
}

