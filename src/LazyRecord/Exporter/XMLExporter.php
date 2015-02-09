<?php
namespace LazyRecord\Exporter;
use LazyRecord\BaseModel;
use LazyRecord\BaseCollection;
use LazyRecord\Schema\SchemaBase;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\SchemaDeclare;
use DOMDocument;
use DOMElement;
use DOMText;

class XMLExporter
{
    public function __construct()
    {

    }

    /**
     * @return DOMDocument
     */
    public function exportCollection(BaseCollection $collection)
    {

    }

    /**
     * @return DOMDocument
     */
    public function exportRecord(BaseModel $record)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = $dom->createElement('export');
        $dom->appendChild($root);
        $this->appendRecord($dom, $root, $record, NULL, false);
        return $dom;
    }

    protected function appendRecordInplace(DOMDocument $dom, DOMElement $root, BaseModel $record, RuntimeSchema $schema = NULL)
    {
        if (!$schema) {
            $schema = $record->getSchema();
        }

        $columns = $schema->getColumns();
        foreach ($columns as $column) {
            $columnElement = $dom->createElement($column->name);
            $columnElement->setAttribute('isa', $column->isa);
            if ($column->type) {
                $columnElement->setAttribute('type', $column->type);
            }
            if ($column->contentType) {
                $columnElement->setAttribute('content-type', $column->contentType);
            }

            $value = $record->getValue($column->name);
            /*
            if ($value instanceof BaseModel) {
                $this->appendRecord($dom, $columnElement, $value);
            } elseif ($value instanceof BaseCollection) {
            }
            */
            $columnElement->appendChild(new DOMText($value));
            $root->appendChild($columnElement);
        }

        foreach ($schema->getRelations() as $rId => $r) {

            if ($r['type'] === SchemaDeclare::has_many) {
                $foreignRecords = $record->get($rId);

                if ($foreignRecords->size() === 0) {
                    continue;
                }

                $relationElement = $dom->createElement($rId);
                $root->appendChild($relationElement);
                $relationElement->setAttribute('type', 'has-many');

                $collectionElement = $dom->createElement('collection');
                $relationElement->appendChild($collectionElement);

                foreach($foreignRecords as $foreignRecord) {
                    $this->appendRecord($dom, $collectionElement, $foreignRecord);
                }

            } else if ($r['type'] === SchemaDeclare::has_one) {

                $foreignRecord = $record->get($rId);
                if (!$foreignRecord) {
                    continue;
                }

                $relationElement = $dom->createElement($rId);
                $root->appendChild($relationElement);
                $relationElement->setAttribute('type', 'has-one');

                $this->appendRecord($dom, $relationElement, $foreignRecord);
            }
        }
    }

    protected function appendRecord(DOMDocument $dom, DOMElement $root, BaseModel $record, RuntimeSchema $schema = NULL)
    {
        if (!$schema) {
            $schema = $record->getSchema();
        }
        $recordElement = $dom->createElement('record');
        $recordElement->setAttribute('class', get_class($record));
        $root->appendChild($recordElement);
        $this->appendRecordInplace($dom, $recordElement, $record, $schema);
    }


}


