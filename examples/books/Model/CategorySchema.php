<?php

namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Mixin\MetadataMixinSchema;
use Magsql\Raw;

class CategorySchema extends DeclareSchema
{
    public function schema()
    {
        $this->table('book_categories');

        $this->column('name')
            ->varchar(128);


        $this->column('parent_id')
            ->integer()
            ->unsigned()
            ->refer(CategorySchema::class)
            ->label('父類別')
            ->default(null)
            ->renderAs('SelectInput', [
                'allow_empty' => 0,
            ]);
        
        $this->many('subcategories', CategorySchema::class, 'parent_id', 'id');
        $this->belongsTo('parent', CategorySchema::class, 'id', 'parent_id');
        $this->many('books', BookSchema::class, 'category_id', 'id');
    }
}
