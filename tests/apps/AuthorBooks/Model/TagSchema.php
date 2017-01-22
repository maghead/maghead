<?php
namespace AuthorBooks\Model;

use Maghead\Schema;

/**
 * @codegen currentUserCan=false
 * @codegen filterColumn=false
 * @codegen validateColumn=false
 * @codegen validateRequire=false
 * @codegen handleValidationError=false
 */
class TagSchema extends Schema
{
    public function schema()
    {
        $this->table('book_tags');

        $this->column('book_id')
            ->integer()
            ->refer('AuthorBooks\\Model\\BookSchema')
            ;
        $this->column('title')
            ->varchar(30)
            ;
    }
}


