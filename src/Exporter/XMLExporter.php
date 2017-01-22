<?php

namespace Maghead\Exporter;

use Maghead\BaseModel;
use Maghead\BaseCollection;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Schema\SchemaInterface;
use DOMDocument;
use DOMElement;
use DOMText;

/*
When exporting a collection, we need to collected the foreign records in a Map

That would cause memory usage issue (too many model objects)

We should also consider the object construction in the import process, we 
should share the same record class in the same collection section.


Here is the specification:

    <export>
        <collection class="TestApp\Model\App">
            ....
        </collection>
    </export>




 */
class XMLExporter
{
    protected $dom;

    protected $root;

    public function __construct()
    {
        $this->dom = new DOMDocument('1.0', 'utf-8');
        $this->root = $this->dom->createElement('export');
        $this->dom->appendChild($this->root);
    }

    /**
     * @return DOMDocument
     */
    public function exportCollection(BaseCollection $collection)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = $dom->createElement('export');
        $dom->appendChild($root);
        // $this->appendRecord($dom, $root, $record, NULL, true);

        $schema = $collection->getSchema();
        $relations = $schema->getRelations();

        // find foreign many-to-many schema
        foreach ($relations as $rel) {
            if ($rel['type'] === Relationship::MANY_TO_MANY) {
                $junctionRel = $relations[$rel['relation_junction']];

                $junctionSchema = $junctionRel->newForeignSchema();
                $foreignRel = $junctionSchema->getRelation($rel['relation_foreign']);
                $foreignCollection = $foreignRel->newForeignCollection();
                $foreignSchema = $foreignRel->newForeignSchema();

                $collectionRoot = $dom->createElement('collection');
                $collectionRoot->setAttribute('schema', get_class($foreignSchema));
                $collectionRoot->setAttribute('class', get_class($foreignCollection));
                $root->appendChild($collectionRoot);
                foreach ($foreignCollection as $record) {
                    $this->appendRecord($dom, $collectionRoot, $record, $foreignSchema, true);
                }
            }
        }

        {
            $collectionRoot = $dom->createElement('collection');
            $collectionRoot->setAttribute('schema', get_class($schema));
            $collectionRoot->setAttribute('class', get_class($collection));
            $root->appendChild($collectionRoot);
            foreach ($collection as $record) {
                $this->appendRecord($dom, $collectionRoot, $record, $schema, true);
            }
        }
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * @return DOMDocument
     */
    public function exportRecord(BaseModel $record)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $root = $dom->createElement('export');
        $dom->appendChild($root);
        $this->appendRecord($dom, $root, $record, null, true);

        return $dom;
    }

    protected function appendRecord(DOMDocument $dom, DOMElement $root, BaseModel $record, SchemaInterface $schema = null,  $recursive = true)
    {
        if (!$schema) {
            $schema = $record->getSchema();
        }
        $recordElement = $dom->createElement('record');
        $recordElement->setAttribute('class', get_class($record));
        $root->appendChild($recordElement);
        $this->appendRecordInplace($dom, $recordElement, $record, $schema, $recursive);
    }

    protected function appendRecordInplace(DOMDocument $dom, DOMElement $root, BaseModel $record, SchemaInterface $schema = null, $recursive = true)
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
            if ($column->isa == 'bool') {
                if ($value === null) {
                    $text = '';
                } else {
                    $text = $value ? '1' : '0';
                }
            } else {
                $text = $value;
            }
            $columnElement->appendChild(new DOMText($text));
            $root->appendChild($columnElement);
        }

        if (!$recursive) {
            return;
        }

        foreach ($schema->getRelations() as $rId => $r) {
            if ($r['type'] === Relationship::HAS_MANY) {
                $foreignRecords = $record->get($rId);

                if (!$foreignRecords || $foreignRecords->size() === 0) {
                    continue;
                }

                $relationElement = $dom->createElement($rId);
                $root->appendChild($relationElement);
                $relationElement->setAttribute('type', 'has-many');

                $collectionElement = $dom->createElement('collection');
                $relationElement->appendChild($collectionElement);

                foreach ($foreignRecords as $foreignRecord) {
                    $this->appendRecord($dom, $collectionElement, $foreignRecord, null, false);
                }
            } elseif ($r['type'] === Relationship::HAS_ONE) {
                $foreignRecord = $record->get($rId);
                if (!$foreignRecord) {
                    continue;
                }

                $relationElement = $dom->createElement($rId);
                $root->appendChild($relationElement);
                $relationElement->setAttribute('type', 'has-one');

                $this->appendRecord($dom, $relationElement, $foreignRecord, null, false);
            } elseif ($r['type'] === Relationship::MANY_TO_MANY) {
                $foreignRecords = $record->get($rId);
                if (!$foreignRecords || $foreignRecords->size() === 0) {
                    continue;
                }

                // $relationElement = $dom->createElement($rId);
                // $relationElement->setAttribute('type', 'many-to-many');
                // $root->ownerDocument->firstChild->appendChild($relationElement);
                // $relationElement->appendChild($collectionElement);

                $collectionElement = $dom->createElement('collection');
                $refNode = $root->ownerDocument->firstChild->insertBefore($collectionElement, $root->ownerDocument->firstChild->firstChild);
                foreach ($foreignRecords as $foreignRecord) {
                    $this->appendRecord($dom, $collectionElement, $foreignRecord, null, false);
                }
            }
        }
    }
}
