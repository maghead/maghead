Plan
====

## Deflator / Inflator accessor

    $this->column('name')
        ->deflator(function($val) { return new DateTime($val); })
        ->inflator(function($val) { return $val->format('c'); });

## Reference builder

build column reference sql for table creation.

## Database table parser

Parse database schemas.

## Diff command

Diff current schemas and databases.

    $ lazy diff
