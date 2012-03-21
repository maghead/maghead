Plan
====

* Deflator / Inflator accessor

    $this->column('name')
        ->deflator(function($val) { return new DateTime($val); })
        ->inflator(function($val) { return $val->format('c'); });
