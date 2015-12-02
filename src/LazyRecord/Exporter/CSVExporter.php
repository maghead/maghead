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

    public function __construct($fd)
    {
        $this->fd = $fd;
    }


    /**
     *
     * int fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' [, string $escape_char = "\" ]]] )
     */
    public function exportCollection(BaseCollection $collection)
    {
        $schema = $collection->getSchema();

        $keys = $schema->getColumnNames();
        fputcsv($this->fd, $keys);
        foreach ($collection as $record) {
            // $array = $record->toInflatedArray();
            $array = $record->toArray();

            $fields = [];
            foreach ($keys as $key) {
                $fields[] = $array[$key];
            }
            fputcsv($this->fd, $fields);
        }
    }

}




