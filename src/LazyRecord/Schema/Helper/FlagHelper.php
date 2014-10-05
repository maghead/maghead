<?php
namespace LazyRecord\Schema\Helper;
use LazyRecord\Schema\SchemaDeclare;

class FlagHelper extends BaseHelper
{
    /**
     * @param string $name The column name
     *
     */
    public function init($name, $label, $checked = false) {
        $this->schema->column($name)
              ->boolean()
              ->label($label)
              ->renderAs('CheckboxInput')
              ->default($checked)
              ;
    }


}




