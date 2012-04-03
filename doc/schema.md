Schema
======

Schema Example
--------------

<?php
use LazyRecord\Schema\SchemaDeclare;

class AddressSchema extends SchemaDeclare
{
    function schema()
    {
        $this->column('author_id')
                ->integer()
                ->label( _('Author') );

        $this->column('address')
                ->varchar(128);

        $this->column('name')
                ->varchar(30)
                ->isa('str') // default, apply String validator
                ->isa('DateTime')  // DateTime object.
                ->isa('int') // Integer object.

                ->validator('ValidatorClass')
                ->validator( array($validator,'method') )
                ->validator('function_name')
                ->validator(function($val) { .... })

                ->filter( function($val) {  
                            return preg_replace('#word#','zz',$val);  
                 })
                ->canonicalizer('CanonicalClass')
                ->canonicalizer(function($val) { return $val; })

                ->default('Default')
                ->default( array('current_timestamp') ) // raw sql string
                ->defaultBuilder(function() { 
                        return date('c');
                })

                ->validValues( 1,2,3,4,5 )
                ->validValues( array( 'label' => 'value'  ) );

        // mixin
        $this->mixin('tests\MetadataMixinSchema');
    }
}
?>

Columns Types
-------------

Varchar:

    $this->column('name')
            ->varchar(64);

Text:

    $this->column('name')
            ->text();

Boolean:

    $this->column('name')
            ->boolean();

Integer:

    $this->column('name')
            ->integer();

Timestamp:

    $this->column('name')
            ->timestamp();

Datetime:

    $this->column('name')
            ->datetime();


Default Value
-------------

    $this->column('name')
        ->varchar(13)
        ->default('default value');

    $this->column('name')
        ->boolean()
        ->default(false);

Default value builder:

    $this->column('name')
        ->boolean()
        ->defaultBuilder(function() { return 'New name'; });


Valid values
------------

<?php
    $this->column('type')
        ...
        ->validValues( 1,2,3,4,5 )

    $this->column('type')
        ...
        ->validValues(array(
            'label' => 'value1',
            'label' => 'value2',
            'label' => 'value3',
            'label' => 'value4',
        ));
?>

Relationships
-------------

Belongs To:

<?php
    $this->belongsTo( 'author', 'author_id' , '\tests\AuthorSchema', 'id' );
?>

Has Many:

<?php
    $this->many( 'addresses', '\tests\AddressSchema', 'author_id', 'id');
    $this->many( 'author_books', '\tests\AuthorBookSchema', 'author_id', 'id');
?>

Many to many

<?php
    $this->manyToMany( 'books', 'author_books' , 'book' );
?>

### Usage

to append:

    $author->address[] = array(  );

    $record = $author->createAddress(array( ... ));  // return false on failure.

to fetch:

    foreach( $author->addresses as $address ) {

    }

to search/find:

    $address = $author->addresses->find(k);


