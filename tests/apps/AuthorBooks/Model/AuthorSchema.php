<?php
namespace AuthorBooks\Model;
use Maghead\Schema;

class AuthorSchema extends Schema
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
            ->inflator(function($value,$record) {
                return $record->name . '(' . $record->email . ')';
            });

        $this->column('identity')
            ->unique()
            ->required()
            ->varchar(128)
            ->findable()
            ;

        $this->column('confirmed')
            ->boolean()
            ->default(false);

        $this->mixin('Maghead\\Schema\\Mixin\\MetadataMixinSchema');

        $this->many('addresses', 'AuthorBooks\Model\AddressSchema', 'author_id', 'id');

        $this->many('unused_addresses', 'AuthorBooks\Model\AddressSchema', 'author_id', 'id')
            ->where()
                ->equal('unused', true);

        $this->many('author_books', 'AuthorBooks\Model\AuthorBookSchema', 'author_id', 'id');

        $this->manyToMany('books', 'author_books' , 'book');
    }
}
