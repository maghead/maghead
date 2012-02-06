<?php
namespace Lazy;


class ExecutiveQueryBuilder extends \SQLBuilder\QueryBuilder
{
    public $callback;

    public function execute()
    {
        $sql = $this->build();
        switch( $this->behavior ) {
            case static::INSERT:
            case static::UPDATE:
            case static::DELETE:
            case static::SELECT:
                return call_user_func( $this->callback, $this, $sql);
                break;
            default:
                throw new \Exception('behavior is not defined.');
                break;
        }
    }
}



