<?php
namespace tests;

class WineCategoryBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\WineCategorySchemaProxy';
const collection_class = 'tests\\WineCategoryCollection';
const model_class = 'tests\\WineCategory';
const table = 'wine_categories';

public static $column_names = array (
  0 => 'name',
  1 => 'id',
);
public static $column_hash = array (
  'name' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
