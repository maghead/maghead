<?php
namespace tests {

    use LazyRecord\Schema\SchemaDeclare;
    use LazyRecord\Schema;

    class NameSchema extends SchemaDeclare
    {
        function schema()
        {

            $this->column('id')
                ->integer()
                ->primary()
                ->autoIncrement();

            $this->column('name')
                ->typeConstraint()
                ->required()
                ->varchar(128);

            $this->column('description')
                ->varchar(128);

            $this->column('category_id')
                ->integer();

            $this->column('address')
                ->varchar(64)
                ->validator( function($val,$args,$record) { 
                    if( preg_match( '/fuck/', $val ) )
                        return array( false , "Please don't" );
                    return array( true , "Good" );
                })
                ->filter( function($val,$args,$record)  { 
                    return str_replace( 'John' , 'XXXX' , $val );
                })
                ->default( function() { 
                    return 'Default Address';
                })
                ->varchar(256);

            $this->column('country')
                ->varchar(12)
                ->required()
                ->validValues( array( 'Taiwan' , 'Taipei' , 'Tokyo' ));

            $this->column('type')
                ->varchar(24)
                ->validValues(function() { 
                    return array(
                        /* description => value */
                        'Type Name A' => 'type-a',
                        'Type Name B' => 'type-b',
                        'Type Name C' => 'type-c',
                    );
                });

            $this->column('confirmed')
                ->isa('bool')
                ->boolean();

            $this->column('date')
                ->date()
                ->isa('DateTime')
                ->deflator( function($val) {
                    if( is_a( $val, 'DateTime',true ) )
                        return $val->format('Y-m-d');
                    elseif( is_integer($val) ) {
                        return strftime( '%Y-%m-%d' , $val );
                    }
                    return $val;
                })
                ->inflator( function($val) { 
                    return new \DateTime( $val );
                });

            $this->seeds('TestSeed');
        }
    }

}
