<?php
namespace LazyRecord\Exporter;
use LazyRecord\BaseModel;
use LazyRecord\BaseCollection;
use LazyRecord\Schema\SchemaBase;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\SchemaInterface;

class CSVExporter
{
    protected $fd;

    protected $delimiter = ','; // default to ',';

    protected $enclosure = '"'; // default to '"'

    protected $escapeChar = "\\"; // default to "\";

    public function __construct($fd, $delimiter = ',', $enclosure = '"', $escapeChar = "\\")
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

    /**
     * Export collection object into CSV file.
     *
     * int fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' [, string $escape_char = "\" ]]] )
     */
    public function exportCollection(BaseCollection $collection)
    {
        $schema = $collection->getSchema();
        $keys = $schema->getColumnNames();

        if (version_compare(phpversion(), '5.5.0') < 0) {
            fputcsv($this->fd, $keys, $this->delimiter, $this->enclosure);
        } else {
            fputcsv($this->fd, $keys, $this->delimiter, $this->enclosure, $this->escapeChar);
        }
        foreach ($collection as $record) {
            // $array = $record->toInflatedArray();
            $array = $record->toArray();

            $fields = [];
            foreach ($keys as $key) {
                $fields[] = $array[$key];
            }
            if (version_compare(phpversion(), '5.5.0') < 0) {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure);
            } else {
                fputcsv($this->fd, $fields, $this->delimiter, $this->enclosure, $this->escapeChar);
            }
        }
    }

}




