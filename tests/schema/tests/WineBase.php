<?php
namespace tests;

class WineBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\WineSchemaProxy';
const collection_class = 'tests\\WineCollection';
const model_class = 'tests\\Wine';
const table = 'wines';

public static $column_names = array (
  0 => 'name',
  1 => 'years',
  2 => 'category_id',
  3 => 'id',
);
public static $column_hash = array (
  'name' => 1,
  'years' => 1,
  'category_id' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
