<?php

namespace Maghead\Schema\Relationship;

class BelongsTo extends Relationship
{
    public function getForeignColumn()
    {
        return $this->data['foreign_column'];
    }

    public function getSelfColumn()
    {
        return $this->data['self_column'];
    }
}
