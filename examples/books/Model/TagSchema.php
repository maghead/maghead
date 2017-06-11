<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;

/**
 * @codegen currentUserCan=false
 * @codegen filterColumn=false
 * @codegen validateColumn=false
 * @codegen validateRequire=false
 * @codegen handleValidationError=false
 */
class TagSchema extends DeclareSchema
{
    public function schema()
    {
        $this->table('book_tags');

        $this->column('book_id')
            ->integer()
            ->unsigned()
            ->required()
            ->refer(BookSchema::class)
            ;

        $this->column('title')
            ->varchar(30)
            ;
    }
}
