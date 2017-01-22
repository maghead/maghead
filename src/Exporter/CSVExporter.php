<?php

namespace Maghead\Exporter;

use Maghead\BaseCollection;
use PDOStatement;
use PDO;

class CSVExporter
{
    protected $fd;

    protected $delimiter = ','; // default to ',';

    protected $enclosure = '"'; // default to '"'

    protected $escapeChar = '\\'; // default to "\";

    public function __construct($fd, $delimiter = ',', $enclosure = '"', $escapeChar = '\\')
    {
        $this->fd = $fd;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escapeChar = $escapeChar;
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
    }

    public function setEscapeChar($char)
    {
        $this->escapeChar = $char;
    }

    public function exportQuery(PDOStatement $stm, array $intersectKeys = null)
    {
        $all = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (empty($all)) {
            return false;
        }
        $columns = array_keys($all[0]);

        if ($intersectKeys) {
            $columns = array_intersect_key($columns, array_flip($intersectKeys));
        }

        $php54 = version_compare(phpversion(), '5.5.0') < 0;
        if ($php54) {
            fputcsv($this->fd, $columns, $this->delimiter, $this->enclosure);
        } else {
            fputcsv($this->fd, $columns, $this->delimiter, $this->enclosure, $this->escapeChar);
        }

        foreach ($all as $row) {
            $fields = [];
            foreach ($keys as $key) {
                $fields[] = $row[$key];
            }
            if ($php54) {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure);
            } else {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure, $this->escapeChar);
            }
        }

        return true;
    }

    /**
     * Export collection object into CSV file.
     *
     * int fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' [, string $escape_char = "\" ]]] )
     */
    public function exportCollection(BaseCollection $collection, array $intersectKeys = null)
    {
        $schema = $collection->getSchema();
        $keys = $schema->getColumnNames();

        if ($intersectKeys) {
            $keys = array_intersect_key($keys, array_flip($intersectKeys));
        }

        $php54 = version_compare(phpversion(), '5.5.0') < 0;
        if ($php54) {
            fputcsv($this->fd, $keys, $this->delimiter, $this->enclosure);
        } else {
            fputcsv($this->fd, $keys, $this->delimiter, $this->enclosure, $this->escapeChar);
        }
        foreach ($collection as $record) {
            $array = $record->toArray();
            $fields = [];
            foreach ($keys as $key) {
                $fields[] = $array[$key];
            }
            if ($php54) {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure);
            } else {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure, $this->escapeChar);
            }
        }

        return true;
    }
}
