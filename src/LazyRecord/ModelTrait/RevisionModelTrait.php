<?php
namespace LazyRecord\ModelTrait;

trait RevisionModelTrait
{

    public function saveAsRevision()
    {
        // back up data
        $modelClass = get_class($this);
        $revision = new $modelClass;

        $this->getData();


    }
    
}



