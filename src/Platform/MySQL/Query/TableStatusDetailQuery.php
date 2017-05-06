<?php

namespace Maghead\Platform\MySQL\Query;

use SQLBuilder\Universal\Query\SelectQuery;

class TableStatusDetailQuery extends SelectQuery
{
    function __construct() {
        parent::__construct();

        $this->select([
            'CONCAT(table_schema, \'.\', table_name) AS name',
            'CONCAT(ROUND(table_rows / 1000000, 2), \'M\') AS rows',

            'CASE WHEN data_length > 1024 * 1024 * 1024 THEN CONCAT(ROUND(data_length / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN data_length > 1024 * 1024        THEN CONCAT(ROUND(data_length / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(data_length / (1024), 2), \'K\')
                                                        END AS data_size',

            'CASE WHEN index_length > 1024 * 1024 * 1024 THEN CONCAT(ROUND(index_length / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN index_length > 1024 * 1024        THEN CONCAT(ROUND(index_length / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(index_length / (1024), 2), \'K\')
                                                        END AS index_size',

            'CASE WHEN (data_length+index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND((data_length+index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN (data_length+index_length) > 1024 * 1024        THEN CONCAT(ROUND((data_length+index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND((data_length+index_length) / (1024), 2), \'K\')
                                                        END AS total_size',

            'ROUND(index_length / data_length, 2) AS index_frac',
        ]);
        $this->from('information_schema.TABLES');
        $this->orderBy('data_length + index_length', 'DESC');
    }

    public function fromDatabase($dbName)
    {
        $this->where()->equal('stat.TABLE_SCHEMA', $dbName);
    }

    public function fromTables($tables)
    {
        $this->where()
            ->in('stat.TABLE_NAME', $tables)
            ;
    }
}
