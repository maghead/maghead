<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Mixin\MetadataMixinSchema;

class AuthorSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128)
            ->findable()
            ;

        $this->column('email')
            ->required()
            ->findable()
            ->varchar(128);

        $this->column('account_brief')
            ->label('Account Brief')
            ->virtual()
            ->inflator(function ($value, $record) {
                return $record->name . '(' . $record->email . ')';
            });

        $this->column('identity')
            ->unique()
            ->required()
            ->varchar(128)
            ->validator('StringLength', ['min' => 3, 'max' => 64])
            ->findable()
            ;

        $this->column('confirmed')
            ->boolean()
            ->default(false);

        $this->mixin(MetadataMixinSchema::class);

        $this->many('addresses', AddressSchema::class, 'author_id', 'id');

        $this->many('unused_addresses', AddressSchema::class, 'author_id', 'id')
            ->where()->equal('unused', true);

        $this->many('author_books', AuthorBookSchema::class, 'author_id', 'id');

        $this->manyToMany('books', 'author_books', 'book');
    }
}
