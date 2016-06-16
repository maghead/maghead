<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\DeclareColumn;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Syntax\Constraint;

class MysqlBuilder extends BaseBuilder
{
    public function prepare()
    {
        return [
            '/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */',
            '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */',
        ];
    }

    public function finalize()
    {
        return [
            '/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */',
            '/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */',
        ];
    }


    /**
    It's possible to raise an error like this:

    ERROR 1215 (HY000): Cannot add foreign key constraint

    Cannot find an index in the referenced table where the
    referenced columns appear as the first columns, or column types
    in the table and the referenced table do not match for constraint.
    Note that the internal storage type of ENUM and SET changed in
    tables created with >= InnoDB-4.1.12, and such columns in old tables
    cannot be referenced by such columns in new tables.
    Please refer to http://dev.mysql.com/doc/refman/5.7/en/innodb-foreign-key-constraints.html for correct foreign key definition.
     */
    public function buildForeignKeyConstraint(Relationship $rel)
    {
        $fSchema = new $rel['foreign_schema'];
        $constraint = new Constraint();
        $constraint->foreignKey($rel['self_column']);
        $references = $constraint->references($fSchema->getTable(), (array) $rel['foreign_column']);
        if ($act = $rel->onUpdate) {
            $references->onUpdate($act);
        }
        if ($act = $rel->onDelete) {
            $references->onDelete($act);
        }
        return $constraint;
    }

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
            switch ($rel['type']) {
                case Relationship::BELONGS_TO:
                if ($name != 'id' && $rel['self_column'] == $name) {
                    $fSchema = new $rel['foreign_schema'];
                    $fColumn = $rel['foreign_column'];
                    $fc = $fSchema->columns[$fColumn];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                    /*
                    if ($rel->onUpdate) {
                        $sql .= ' ON UPDATE ' . $rel->onUpdate;
                    }
                    if ($rel->onDelete) {
                        $sql .= ' ON DELETE ' . $rel->onDelete;
                    }
                    */
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

