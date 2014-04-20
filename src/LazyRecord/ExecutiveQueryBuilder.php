<?php
namespace LazyRecord;
use Exception;
use SQLBuilder\SQLBuilder;

class ExecutiveQueryBuilder extends \SQLBuilder\QueryBuilder
{
    public $callback;

    public function execute() {
        return call_user_func($this->callback, $this, $this->build() );
    }
}



