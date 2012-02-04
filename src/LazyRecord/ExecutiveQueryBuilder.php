<?php
namespace LazyRecord;


class ExecutiveQueryBuilder extends \SQLBuilder\QueryBuilder
{
    public $caller;

    public function execute()
    {
        $caller = $this->caller;
        $sql = $this->build();

        switch( $this->behavior ) {
            case static::INSERT:
                break;
            case static::UPDATE:
                break;
            case static::DELETE:
                break;
            case static::SELECT:
                break;
        }

        // $caller->
    }

}



