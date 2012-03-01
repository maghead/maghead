<?php
namespace tests;
use Lazy\Schema\SchemaDeclare;
use Lazy\Schema;

class AuthorSchema extends SchemaDeclare
{
    function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);

        $this->column('email')
            ->isa('str')
            ->required()
            ->varchar(128);

        $this->column('identity')
            ->isa('str')
            ->unique()
            ->required()
            ->varchar(128);

        $this->column('confirmed')
            ->isa('bool')
            ->default(false)
            ->boolean();

        $this->mixin('tests\MetadataMixinSchema');

        // $this->belongsTo( '\tests\AuthorBookSchema' , 'author_id' );
    }

}
