Schema
======

Schema Example
--------------

```php
<?php
use Maghead\Schema;

class AddressSchema extends Schema
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
        $this->mixin('TestApp\MetadataMixinSchema');


        // optional


        $this->writeTo('master');   // data source for writing
        $this->readFrom('slave');   // data source for reading
    }
}
```

Schema Column
-------------

To define a column:

```php
$this->column('name');
```

The column method returns a `Column` object, the default 
type is text.

To specify columm type, simply call `type` method

```
$this->column('name')
    ->type('integer');
```

Currently our column provides many short-hand methods for types, 
e.g.

```php
$this->column('foo')->integer();
$this->column('foo')->float();
$this->column('foo')->varchar(24);
$this->column('foo')->text();
$this->column('foo')->binary();
```


Columns Types
-------------

Varchar:

```php
    $this->column('name')
            ->varchar(64);
```

Text:

```php
    $this->column('name')
            ->text();
```

Boolean:

```php
    $this->column('name')
            ->boolean();
```

Integer:

```php
    $this->column('name')
            ->integer();
```

Timestamp:

```php
    $this->column('name')
            ->timestamp();
```

Datetime:

```php
    $this->column('name')
            ->datetime();
```

Default Value
-------------

```php
    $this->column('name')
        ->varchar(13)
        ->default('default value');

    $this->column('name')
        ->boolean()
        ->default(false);
```

Default value builder:

```php
    $this->column('name')
        ->boolean()
        ->default(function($record,$args) { return 'New name'; });
```

The callback function's prototype: ($record, $args)

The `$args` is the arguments of create action.

Valid values
------------

```php
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
```

Relationship
------------

### Belongs to

`belongsTo(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name = 'id')`

```php
$this->belongsTo( 'author' , '\TestApp\AuthorSchema', 'id' , 'author_id' );
$this->belongsTo( 'address' , '\TestApp\AddressSchema', 'address_id' );
```

### Has One

`one(accessor_name, self_column_name, foreign_schema_class_name, foreign_schema_column_name)`

```php
$this->one( 'author', 'author_id', '\TestApp\AuthorSchema' , 'id' );
```

### Has Many

`many(accessor_name, foreign_schema_class_name, foreign_schema_column_name, self_column_name )`

```php
$this->many( 'addresses', '\TestApp\AddressSchema', 'author_id', 'id');
$this->many( 'author_books', '\TestApp\AuthorBookSchema', 'author_id', 'id');
```

To define many to many relationship:

```php
$this->manyToMany( 'books', 'author_books' , 'book' );
```

### Relationship Usage

To append:

```php
$author->address[] = array(  );
$record = $author->address->create(array( ... ));  // return false on failure.
```

To fetch:

```php
foreach( $author->addresses as $address ) {

}
```

To search/find:

```php
$address = $author->addresses->load(k);
```

## RuntimeSchema API

To get schema object in model:

```php
$schema = $this->getSchema();   // RuntimeSchema
```

To check if a schema contains column:

```php
$exists = $schema->hasColumn('name');
```

To get RuntimeColumn object from RuntimeSchema:

```php
$column = $schema->getColumn('name'); // RuntimeColumn
```

To get column names (excluding virtual columns):

```php
$columnNames = $schema->getColumnNames();  // array('id','name')
```

To get column names (including virtual columns):

```php
$columnNames = $schema->getColumnNames(true);
```

To get RuntimeColumn objects (excluding virtual columns)

```php
$columns = $schema->getColumns( false );
```

To get RuntimeColumn objects (including virtual columns)

```php
$columns = $schema->getColumns( true );
```

To create a model object from schema object:

```php
$model = $schema->newModel();
```

To create a new collection object from schema object:

```php
$collection = $schema->newCollection();
```





