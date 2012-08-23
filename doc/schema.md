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
                ->default(function() { 
                        return date('c');
                })

                ->validValues( 1,2,3,4,5 )
                ->validValues( array( 'label' => 'value'  ) );

        // mixin
        $this->mixin('tests\MetadataMixinSchema');


        // optional


        $this->writeTo('master');   // data source for writing
        $this->readFrom('slave');   // data source for reading
    }
}
?>

Schema Column
-------------

To define a column:

    $this->column('name');

The column method returns a `Column` object, the default 
type is text.

To specify columm type, simply call `type` method

    $this->column('name')
        ->type('integer');

Currently our column provides many short-hand methods for types, 
e.g.

    $this->column('foo')->integer();
    $this->column('foo')->float();
    $this->column('foo')->varchar(24);
    $this->column('foo')->text();
    $this->column('foo')->binary();



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
        ->default(function($record,$args) { return 'New name'; });

The callback function's prototype: ($record, $args)

The `$args` is the arguments of create action.

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

Belongs To: `(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name = 'id')`

<?php
    $this->belongsTo( 'author' , '\tests\AuthorSchema', 'id' , 'author_id' );
    $this->belongsTo( 'address' , '\tests\AddressSchema', 'address_id' );
?>


Has One: `(accessor_name, self_column_name, foreign_schema_class_name, foreign_schema_column_name)`

<?php 
    $this->one( 'author', 'author_id', '\tests\AuthorSchema' , 'id' );
?>

Has Many: `(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name )`

<?php
    $this->many( 'addresses', '\tests\AddressSchema', 'author_id', 'id');
    $this->many( 'author_books', '\tests\AuthorBookSchema', 'author_id', 'id');
?>

To define many to many relationship:

<?php
    $this->manyToMany( 'books', 'author_books' , 'book' );
?>

### Usage

To append:

    $author->address[] = array(  );

    $record = $author->createAddress(array( ... ));  // return false on failure.

To fetch:

    foreach( $author->addresses as $address ) {

    }

To search/find:

    $address = $author->addresses->find(k);

## RuntimeSchema API

To get schema object in model:

    $schema = $this->getSchema();   // RuntimeSchema

To check if a schema contains column:

    $exists = $schema->hasColumn('name');

To get RuntimeColumn object from RuntimeSchema:

    $column = $schema->getColumn('name'); // RuntimeColumn

To get column names (excluding virtual columns):

    $columnNames = $schema->getColumnNames();  // array('id','name')

To get column names (including virtual columns):

    $columnNames = $schema->getColumnNames(true);

To get RuntimeColumn objects (excluding virtual columns)

    $columns = $schema->getColumns( false );

To get RuntimeColumn objects (including virtual columns)

    $columns = $schema->getColumns( true );

To create a model object from schema object:

    $model = $schema->newModel();

To create a collection object from schema object:

    $collection = $schema->newCollection();

