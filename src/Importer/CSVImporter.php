<?php

namespace Maghead\Importer;

use Maghead\Runtime\Model;

class CSVImporter
{
    /**
     * @var Maghead\Runtime\Model
     */
    protected $model;

    /**
     * @var array
     *
     * [
     *    field idx => schema column name
     *    field idx => schema column name
     * ]
     */
    protected $columnMap;

    public function __construct(Model $model, array $columnMap = null)
    {
        $this->model = $model;
        $this->columnMap = $columnMap;
    }

    /**
     * array fgetcsv ( resource $handle [, int $length = 0 [, string $delimiter = "," [, string $enclosure = '"' [, string $escape = "\" ]]]] ).
     */
    public function importResource($fd, $unsetPrimaryKey = false)
    {
        $schema = $this->model->getSchema();
        if ($this->columnMap) {
            $columnNames = $this->columnMap;
        } else {
            $columnNames = fgetcsv($fd);
        }
        while (($fields = fgetcsv($fd)) !== false) {
            $args = array();
            foreach ($columnNames as $idx => $columnName) {
                $columnValue = $fields[$idx];
                $args[$columnName] = $columnValue;
            }

            if ($unsetPrimaryKey) {
                if ($pk = $schema->primaryKey) {
                    unset($args[$pk]);
                }
            }

            $ret = $this->model->create($args);
        }
    }
}
