<?php
namespace TestApp\Model;
use Maghead\Schema;

class UserSchema extends Schema
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
