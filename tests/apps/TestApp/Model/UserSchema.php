<?php
namespace TestApp\Model;

use Maghead\Schema\DeclareSchema;

class UserSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('account')
            ->isa('str')
            ->unique()
            ->notNull()
            ->label('Account')
            ->varchar(128);

        $this->column('password')
            ->isa('str')
            ->label('Password')
            ->varchar(256);
    }
}
