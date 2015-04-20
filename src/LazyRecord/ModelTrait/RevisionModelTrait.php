<?php
namespace LazyRecord\ModelTrait;
use LogicException;
use Exception;

trait RevisionModelTrait
{

    public function saveWithRevision()
    {
        // back up data
        $modelClass = get_class($this);
        $rev = new $modelClass;

        $data = $this->toArray();

        $schema = $rev->getSchema();

        if ($primaryKey = $schema->primaryKey) {
            // Remove the primary key and create new revision

            if ( !isset($data[$primaryKey])) {
                throw new LogicException("An existing record is required for making new revision record. Please setup a primary key or create a record.");
            }
            $id = $data[$primaryKey];

            unset($data[$primaryKey]);

            $data['revision_parent_id'] = $id;

            // Copy the root reivision ID
            if (isset($data['revision_root_id'])) {
                $data['revision_root_id'] = $data['revision_root_id'];
            } else {
                // the current one is the root.
                $data['revision_root_id'] = $id;
            }
        }
        $ret = $rev->create($data);
        if ($ret->error) {
            throw $ret->toException("Can't create revision.");
        }
        $ret = $this->save();
        if ($ret->error) {
            throw $ret->toException("Can't save record.");
        }
        return $rev;
    }
    
}



