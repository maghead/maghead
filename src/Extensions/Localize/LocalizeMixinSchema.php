<?php

namespace Maghead\Extensions\Localize;

use Maghead\Schema\MixinDeclareSchema;

class LocalizeMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
    }

    public function postSchema()
    {
        if ($schema = $this->getParentSchema()) {
            foreach ($schema->getColumns() as $column) {
                if (!empty($column->locales)) {
                    // expand column
                    foreach ($column->locales as $locale) {
                        $newColumn = clone $column;
                        $newColumn->name($column->name.'_'.$locale);
                        $schema->addColumn($newColumn);
                    }
                }
            }
        }
    }
}
