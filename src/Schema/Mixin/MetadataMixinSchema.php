<?php

namespace Maghead\Schema\Mixin;

use Maghead\Schema\MixinDeclareSchema;
use Maghead\Schema\DeclareSchema;
use DateTime;
use Magsql\Raw;

class MetadataMixinSchema extends MixinDeclareSchema
{
    /*
    The following SQL is MySQL 5.5 compatible:

        DROP TABLE IF EXISTS t1;
        CREATE TABLE t1 (
          `created_at` TIMESTAMP NULL DEFAULT 0,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

    The following SQL is MySQL 5.6 compatible (multiple CURRENT_TIMESTAMP column is supported)

        DROP TABLE IF EXISTS t1;
        CREATE TABLE t1 (
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
            
    In MySQL, timestamp columns are default to "NOT NULL"


    Here is the upgrade note from MySQL 5.6

    In MySQL, the TIMESTAMP data type differs in nonstandard ways from other data
    types:

    TIMESTAMP columns not explicitly declared with the NULL attribute are assigned
        the NOT NULL attribute. (Columns of other data types, if not explicitly
        declared as NOT NULL, permit NULL values.) Setting such a column to NULL
        sets it to the current timestamp.

    The first TIMESTAMP column in a table, if not declared with the NULL attribute
    or an explicit DEFAULT or ON UPDATE clause, is automatically assigned the
    DEFAULT CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP attributes.

    TIMESTAMP columns following the first one, if not declared with the NULL
    attribute or an explicit DEFAULT clause, are automatically assigned DEFAULT
    '0000-00-00 00:00:00' (the “zero” timestamp). For inserted rows that specify
    no explicit value for such a column, the column is assigned '0000-00-00 00:00:00' and no warning occurs.

     */
    public function schema()
    {
        $this->column('created_at')
            ->timestamp()
            ->isa('DateTime')
            ->null() // explicitly declare the "NULL"
            ->renderAs('DateTimeInput')
            ->label('Created At')
            ->default(function() {
                return new \DateTime;
            })
            ;
        $this->column('updated_at')
            ->timestamp()
            ->notNull() // explicitly declare the "NOT NULL"
            ->isa('DateTime')
            ->renderAs('DateTimeInput')
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ->label('Updated At')
            ;

        $this->parentSchema->classes->baseModel->useTrait(\Maghead\Extensions\Metadata\AgeModelTrait::class);
    }
}
