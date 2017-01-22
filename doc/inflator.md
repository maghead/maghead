Inflator
========

    Maghead\Inflator::register('datetime', function($value) { 
        return new DateTime($value);
    })

Then you can define a new type called 'datetime' in your schema column:

    $this->column('created_at')
        ->isa('datetime');

You can also define a inflator callback in your schema column:

    $this->column('created_at')
        ->inflator( function($value, $record) { 
            return new DateTime( $value ); 
        });
