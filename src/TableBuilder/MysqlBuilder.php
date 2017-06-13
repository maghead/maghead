<?php

namespace Maghead\TableBuilder;

use Maghead\Schema\Schema;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Schema\Relationship\BelongsTo;
use Maghead\Schema\DeclareColumn;
use Maghead\Schema\SchemaLoader;
use Magsql\ArgumentArray;
use Magsql\Universal\Syntax\Constraint;

class MysqlBuilder extends TableBuilder
{
    public function prepare()
    {
        return [
            'SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0',
            'SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0',
        ];
    }

    public function finalize()
    {
        return [
            'SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS',
            'SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS',
        ];
    }

    public function createTable(Schema $schema)
    {
        $sql = 'CREATE TABLE  IF NOT EXISTS ';

        $sql .= $this->driver->quoteIdentifier($schema->getTable());

        $sql .= " (\n";

        $columnSqls = array();
        foreach ($schema->columns as $name => $column) {
            if ($column->virtual) {
                continue;
            }
            $columnSqls[] = '  '.$this->buildColumn($schema, $column);
        }
        $referencesSqls = $this->buildForeignKeys($schema);
        $sql .= implode(",\n", array_merge($columnSqls, $referencesSqls));

        $sql .= "\n) ENGINE=InnoDB;\n";

        return $sql;
    }

    /**
     * Please refer to http://dev.mysql.com/doc/refman/5.7/en/innodb-foreign-key-constraints.html for correct foreign key definition.
     */
    public function buildForeignKeyConstraint(Relationship $rel)
    {
        $schemaClass = $rel['foreign_schema'];

        $fSchema = SchemaLoader::load($schemaClass);

        $constraint = new Constraint();
        $constraint->foreignKey($rel['self_column']);

        if (empty($rel['foreign_column'])) {
            throw new \Exception("foreign key column of {$rel->accessor} is empty.");
        }

        $keys = (array) $rel['foreign_column'];

        $references = $constraint->references($fSchema->getTable(), $keys);
        if ($act = $rel->onUpdate) {
            $references->onUpdate($act);
        }
        if ($act = $rel->onDelete) {
            $references->onDelete($act);
        }

        return $constraint;
    }

    /**
     * Override buildColumn to support inline reference.
     *
     *  MySQL Syntax:
     *
     *      reference_definition:
     *      REFERENCES tbl_name (index_col_name,...)
     *          [MATCH FULL | MATCH PARTIAL | MATCH SIMPLE]
     *          [ON DELETE reference_option]
     *          [ON UPDATE reference_option]
     *      reference_option:
     *          RESTRICT | CASCADE | SET NULL | NO ACTION
     *  A reference example:
     *
     *      PRIMARY KEY (`idEmployee`) ,
     *      CONSTRAINT `fkEmployee_Addresses`
     *      FOREIGN KEY `fkEmployee_Addresses` (`idAddresses`)
     *      REFERENCES `schema`.`Addresses` (`idAddresses`)
     *      ON DELETE NO ACTION
     *      ON UPDATE NO ACTION
     *
     *  FOREIGN KEY (`order_uuid`) REFERENCES orders(`uuid`)
     */
    public function buildColumn(Schema $schema, DeclareColumn $column)
    {
        $name = $column->name;
        $isa = $column->isa ?: 'str';
        if (!$column->type && $isa == 'str') {
            $column->type = 'text';
        }

        $args = new ArgumentArray();
        $sql = $column->buildDefinitionSql($this->driver, $args);


        // BUILD COLUMN REFERENCE

        // track(
        //     FOREIGN KEY(trackartist) REFERENCES artist(artistid)
        //     artist_id INTEGER REFERENCES artist
        // )

        // And here is the important part:

        // Furthermore, MySQL parses but ignores “inline REFERENCES specifications”
        // (as defined in the SQL standard) where the references
        // are defined as part of the column specification.

        // MySQL accepts REFERENCES clauses only when specified as part of a
        // separate FOREIGN KEY specification. For storage engines that do not
        // support foreign keys (such as MyISAM), MySQL Server parses and ignores
        // foreign key specifications.

        // A column with foreign key should not be nullable.
        // @see http://stackoverflow.com/questions/10028214/add-foreign-key-to-existing-table

        $pk = $schema->getPrimaryKey();
        foreach ($schema->relations as $rel) {
            if ($rel instanceof BelongsTo) {
                if ($name !== $pk && $rel['self_column'] == $name) {
                    $fSchema = new $rel['foreign_schema']();
                    $fColumn = $rel['foreign_column'];
                }
            }
        }
        return $sql;

    }

    public function dropTable(Schema $schema)
    {
        return 'DROP TABLE IF EXISTS '
            .$this->driver->quoteIdentifier($schema->getTable())
            .';';
    }
}
