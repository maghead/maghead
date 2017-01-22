<?php

namespace Maghead\TableParser;

use PDO;
use Maghead\Schema;
use Maghead\Schema\DeclareSchema;

class PgsqlTableParser extends BaseTableParser
{
    public function getTables()
    {
        $stm = $this->connection->query('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\';');
        $rows = $stm->fetchAll(PDO::FETCH_NUM);

        return array_map(function ($row) { return $row[0]; }, $rows);
    }

    public function reverseTableSchema($table, $referenceSchema = null)
    {
        /*
         * postgresql information schema column descriptions
         *
         * @see http://www.postgresql.org/docs/8.1/static/infoschema-columns.html
         */
        $sql = "SELECT * FROM information_schema.columns WHERE table_name = '$table';";
        $stm = $this->connection->query($sql);
        $schema = new DeclareSchema();
        $schema->columnNames = $schema->columns = array();
        $rows = $stm->fetchAll(PDO::FETCH_OBJ);

        /*
         * more detailed attributes
         *
         * > select * from pg_attribute, pg_type where typname = 'addresses';
         * > select * from pg_attribute, pg_type where typname = 'addresses' and attname not in ('cmin','cmax','ctid','oid','tableoid','xmin','xmax');
         *
         * > SELECT
         *      a.attname as "Column",
         *      pg_catalog.format_type(a.atttypid, a.atttypmod) as "Datatype"
         *  FROM
         *      pg_catalog.pg_attribute a
         *  WHERE
         *      a.attnum > 0
         *      AND NOT a.attisdropped
         *      AND a.attrelid = (
         *          SELECT c.oid
         *          FROM pg_catalog.pg_class c
         *              LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
         *          WHERE c.relname ~ '^(books)$'  
         *              AND pg_catalog.pg_table_is_visible(c.oid)
         *      )
         *  ;
         *
         * @see http://notfaq.wordpress.com/2006/07/29/sql-postgresql-get-tables-and-columns/
         */
        foreach ($rows as $row) {
            $column = $schema->column($row->column_name);
            if ($row->is_nullable === 'YES') {
                $column->null();
            } else {
                $column->notNull();
            }

            $type = $row->data_type;

            $typeInfo = TypeInfoParser::parseTypeInfo($type);
            if ($typeInfo->type === 'varchar') {
                $type = 'varchar('.$row->character_maximum_length.')';
            }

            $column->type($type);

            $isa = null;
            if (preg_match('/^(text|varchar|character)/i', $type)) {
                $isa = 'str';
            } elseif (preg_match('/^(int|bigint|smallint|integer)/i', $type)) {
                $isa = 'int';
            } elseif (preg_match('/^(timestamp|date)/i', $type)) {
                $isa = 'DateTime';
            } elseif ($type === 'boolean') {
                $isa = 'bool';
            }

            if ($isa) {
                $column->isa($isa);
            }

            if ($typeInfo->length) {
                $column->length($typeInfo->length);
            }
            if ($typeInfo->precision) {
                $column->decimals($typeInfo->precision);
            }

            // $row->ordinal_position
            // $row->data_type
            // $row->column_default
            // $row->character_maximum_length
        }

        return $schema;
    }
}
