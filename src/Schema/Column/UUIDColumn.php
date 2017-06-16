<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;
use Maghead\Schema\DeclareSchema;

use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Driver\SQLiteDriver;
use Magsql\ArgumentArray;
use Magsql\ToSqlInterface;

class UUIDColumn extends DeclareColumn
{
    /**
     * BINARY(16) is the best column type for UUID.
     *
     * @see http://mysqlserverteam.com/storing-uuid-values-in-mysql-tables/
     */
    public function __construct(DeclareSchema $schema, $name = 'uuid', $type = 'BINARY', $length = 16)
    {
        parent::__construct($schema, $name);
        $this->type($type)
            ->isa('str')
            ->length($length)
            ->notNull()
            ;
    }

    public function buildTypeName(BaseDriver $driver)
    {
        if ($driver instanceof PgSQLDriver) {
            // FIXME:
            // we have an issue when fetching bytea column from postgresql:
            //
            // PDOException: SQLSTATE[22021]: Character not in repertoire: 7 ERROR:  invalid byte sequence for encoding "UTF8": 0x8b
            //
            // - http://stackoverflow.com/questions/16001238/writing-to-a-bytea-field-error-invalid-byte-sequence-for-encoding-utf8-0x9
            // - https://github.com/laravel/framework/issues/10847
            // - uuid https://www.postgresql.org/docs/9.1/static/datatype-uuid.html
            // return 'bytea';
        }
        return parent::buildTypeName($driver);
    }
}
