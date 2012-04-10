<?php
namespace LazyRecord;

/**
 * deflate object value into database
 */
class Deflator
{

    static function deflate($value, $isa)
    {
        /* respect the data type to inflate value */
        if( $isa === 'int' ) {
            return (int) $value;
        }
        elseif( $isa === 'str' ) {
            return (string) $value;
        }
        elseif( $isa === 'double' ) {
            return (double) $value;
        }
        elseif( $isa === 'float' ) {
            return (float) $value;
        }
        elseif( $isa == 'DateTime' ) {
            if( is_a($value, 'DateTime') ) {
                return $value->format( DateTime::ATOM );
            }
            return $value; // might return ""
        }
        elseif( $isa == 'bool' ) 
        {
            /**
             * PDO can't accept false or true boolean value, can only accept string
             * https://bugs.php.net/bug.php?id=33876
             *
             * should cast to string for now.
             *
             * in this case, work for SQLite
             *
             * MySQL result table:
             *  +----+-------------------+-------------+-------------+---------+---------+------+-----------+
             *  | id | name              | description | category_id | address | country | type | confirmed |
             *  +----+-------------------+-------------+-------------+---------+---------+------+-----------+
             *  |  5 | Foo with "1"      | NULL        |        NULL | NULL    | Taipei  | NULL |         1 |
             *  |  6 | Foo with "TRUE"   | NULL        |        NULL | NULL    | Taipei  | NULL |         0 |
             *  |  7 | Foo with TRUE     | NULL        |        NULL | NULL    | Taipei  | NULL |         1 |
             *  |  8 | Foo with FALSE    | NULL        |        NULL | NULL    | Taipei  | NULL |         0 |
             *  |  9 | Foo with "FALSE"  | NULL        |        NULL | NULL    | Taipei  | NULL |         0 |
             *  | 10 | Foo with "0"      | NULL        |        NULL | NULL    | Taipei  | NULL |         0 |
             *  +----+-------------------+-------------+-------------+---------+---------+------+-----------+
             * PGSQL Result table:
             *
             *  lazy_test=# select * from names; 
             *  id |       name        | description | category_id | address | country | type | confirmed 
             *  ----+-------------------+-------------+-------------+---------+---------+------+-----------
             *  25 | Foo with "1"      |             |             |         | Taipei  |      | t
             *  26 | Foo with "TRUE"   |             |             |         | Taipei  |      | t
             *  27 | Foo with TRUE     |             |             |         | Taipei  |      | t
             *  28 | Foo with "FALSE"  |             |             |         | Taipei  |      | f
             *  29 | Foo with "0"      |             |             |         | Taipei  |      | f
             *  (5 rows)
             */
            if( is_bool($value) ) {
                return $value ? 1 : 0;
            }
            elseif( is_string($value) ) {
                if( $value === '0' || strncasecmp($value,'false',5) == 0 ) {
                    return $value = 0;
                }
                elseif( $value === '1' ||  strncasecmp($value,'true',4 ) == 0  ) {
                    return $value = 1;
                }
            }
            elseif( is_null($value) ) {
                return $value = null;
            }
            return $value = (boolean) $value ? 1 : 0;
        }
        elseif( $isa == 'float' ) {
            return (float) $value;
        }
        return $value;
    }

}

