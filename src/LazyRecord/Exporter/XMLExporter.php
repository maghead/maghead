<?php
namespace LazyRecord\Exporter;
use LazyRecord\BaseModel;
use LazyRecord\BaseCollection;
use LazyRecord\Schema\SchemaBase;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\SchemaDeclare;
use DOMDocument;
use DOMElement;
use DOMText;


/*
When exporting a collection, we need to collected the foreign records in a Map

That would cause memory usage issue (too many model objects)

We should also consider the object construction in the import process, we 
should share the same record class in the same collection section.

 */
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
        $this->appendRecord($dom, $root, $record, NULL, true);
        return $dom;
    }

    protected function appendRecordInplace(DOMDocument $dom, DOMElement $root, BaseModel $record, RuntimeSchema $schema = NULL, $recursive = true)
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
            $columnElement->appendChild(new DOMText($value));
            $root->appendChild($columnElement);
        }

        if (!$recursive) {
            return;
        }

        foreach ($schema->getRelations() as $rId => $r) {

            if ($r['type'] === Relationship::HAS_MANY) {
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
                    $this->appendRecord($dom, $collectionElement, $foreignRecord, NULL, false);
                }

            } elseif ($r['type'] === Relationship::HAS_ONE) {

                $foreignRecord = $record->get($rId);
                if (!$foreignRecord) {
                    continue;
                }

                $relationElement = $dom->createElement($rId);
                $root->appendChild($relationElement);
                $relationElement->setAttribute('type', 'has-one');

                $this->appendRecord($dom, $relationElement, $foreignRecord, NULL, false);
            } elseif ($r['type'] === Relationship::MANY_TO_MANY) {

                $foreignRecords = $record->get($rId);
                if ($foreignRecords->size() === 0) {
                    continue;
                }

                // $relationElement = $dom->createElement($rId);
                // $relationElement->setAttribute('type', 'many-to-many');
                // $root->ownerDocument->firstChild->appendChild($relationElement);
                // $relationElement->appendChild($collectionElement);

                $collectionElement = $dom->createElement('collection');
                $refNode = $root->ownerDocument->firstChild->insertBefore($collectionElement, $root->ownerDocument->firstChild->firstChild);
                foreach ($foreignRecords as $foreignRecord) {
                    $this->appendRecord($dom, $collectionElement, $foreignRecord, NULL, false);
                }
            }
        }
    }

    protected function appendRecord(DOMDocument $dom, DOMElement $root, BaseModel $record, RuntimeSchema $schema = NULL,  $recursive = true)
    {
        if (!$schema) {
            $schema = $record->getSchema();
        }
        $recordElement = $dom->createElement('record');
        $recordElement->setAttribute('class', get_class($record));
        $root->appendChild($recordElement);
        $this->appendRecordInplace($dom, $recordElement, $record, $schema, $recursive);
    }


}


