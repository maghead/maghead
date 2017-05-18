<?php

namespace Maghead\Platform\MySQL\Query;

use Magsql\Universal\Query\SelectQuery;

class IndexStatsQuery extends SelectQuery
{
    public function __construct() {
        parent::__construct();
        $this->select([
            'stat.TABLE_NAME',
            'CONCAT(stat.INDEX_NAME, " (", GROUP_CONCAT(DISTINCT stat.COLUMN_NAME ORDER BY stat.SEQ_IN_INDEX ASC), ")")' => 'COLUMNS',
            'stat.INDEX_TYPE',
            'stat.NULLABLE',
            'CASE stat.NON_UNIQUE WHEN 1 THEN \'\' WHEN 0 THEN \'UNIQUE\' END' => 'UNIQ',
            'SUM(index_stat.stat_value)' => 'pages',
            'CONCAT(ROUND((SUM(index_stat.stat_value) * @@Innodb_page_size) / 1024 / 1024, 1), "MB")' => 'PAGE_SIZE',
            'stat.COMMENT',
        ]);
        $this->from('information_schema.STATISTICS stat');

        $this->join('mysql.innodb_index_stats', 'index_stat', 'LEFT')
            ->on('index_stat.database_name = stat.TABLE_SCHEMA 
                AND index_stat.table_name = stat.TABLE_NAME 
                AND index_stat.index_name = stat.INDEX_NAME');

        $this->groupBy('stat.INDEX_NAME');
        $this->groupBy('stat.TABLE_NAME');
        $this->groupBy('stat.TABLE_SCHEMA');

        $this->orderBy('stat.TABLE_SCHEMA', 'ASC');
        $this->orderBy('stat.TABLE_NAME', 'ASC');
        $this->orderBy('stat.INDEX_NAME', 'ASC');
        $this->orderBy('stat.SEQ_IN_INDEX', 'ASC');
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
