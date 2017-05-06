<?php

namespace Maghead\Platform\MySQL\Query;

use SQLBuilder\Universal\Query\SelectQuery;

class TableStatusSummaryQuery extends SelectQuery
{
    function __construct() {
        parent::__construct();

        $this->select([
            'CONCAT(table_schema, \'.\', table_name) AS name',
            'CONCAT(ROUND(SUM(table_rows) / 1000000, 2), \'M\') AS rows',
            'CASE WHEN SUM(data_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(data_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(data_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(data_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(data_length) / 1024, 2), \'K\')
                                                        END AS data_size',
            'CASE WHEN SUM(index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(index_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(index_length) / (1024), 2), \'K\')
                                                        END AS index_size',
            'CASE WHEN SUM(data_length+index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(data_length+index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(data_length+index_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(data_length+index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(data_length+index_length) / (1024), 2), \'K\')
                                                        END AS total_size',
        ]);
        $this->from('information_schema.TABLES');
        $this->groupBy('name');
    }

    public function fromDatabase($dbName)
    {
        $this->where()->equal('TABLE_SCHEMA', $dbName);
    }

    public function fromTables($tables)
    {
        $this->where()->in('TABLE_NAME', $tables);
    }
}
