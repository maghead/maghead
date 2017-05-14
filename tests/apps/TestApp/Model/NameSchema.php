<?php

namespace TestApp\Model;

use Maghead\Schema\DeclareSchema;

class NameSeed
{
    public static function seed()
    {
        $ret = Name::create(['name' => 'Add','country' => 'Taiwan','address' => 'Address' ]);
    }
}

class NameSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('id', 'ai-pk');

        $this->column('name')
            ->required()
            ->varchar(128);

        $this->column('description')
            ->varchar(128);

        $this->column('category_id')
            ->integer();

        $this->column('address')
            ->varchar(64)
            ->validator(function ($val, $args) {
                if (preg_match('/fuck/', $val)) {
                    return array( false , "Please don't" );
                }
                return array( true , "Good" );
            })
            ->filter(function ($val, $args) {
                return str_replace('John', 'XXXX', $val);
            })
            ->default(function () {
                return 'Default Address';
            })
            ->varchar(256);

        $this->column('country')
            ->varchar(12)
            ->required()
            ->index()
            ->validValues(array( 'Taiwan' , 'Taipei' , 'Tokyo', 'US'));

        $this->column('type')
            ->varchar(24)
            ->validValues(function () {
                return array(
                    /* description => value */
                    'Type Name A' => 'type-a',
                    'Type Name B' => 'type-b',
                    'Type Name C' => 'type-c',
                );
            });

        $this->column('confirmed')->boolean();

        $this->column('date')
            ->date()
            ->isa('DateTime')
            ->deflator(function ($val) {
                if ($val instanceof \DateTime) {
                    return $val->format('Y-m-d');
                } elseif (is_integer($val)) {
                    return strftime('%Y-%m-%d', $val);
                }
                return $val;
            })
            ->inflator(function ($val) {
                return new \DateTime($val);
            });

        $this->useSeeds('NameSeed');
    }

    public function seeds()
    {
        return [
            ['name' => 'Tim Cook','country' => 'US', 'address' => 'Tim Cook\'s Address' ],
            ['name' => 'Tsai','country' => 'Taipei', 'address' => 'Tsia' ],
        ];
    }
}
