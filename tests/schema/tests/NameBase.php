<?php
namespace tests;

class NameBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\NameSchemaProxy';
const collection_class = 'tests\\NameCollection';
const model_class = 'tests\\Name';
const table = 'names';

public static $column_names = array (
  0 => 'id',
  1 => 'name',
  2 => 'description',
  3 => 'category_id',
  4 => 'address',
  5 => 'country',
  6 => 'type',
  7 => 'confirmed',
  8 => 'date',
);
public static $column_hash = array (
  'id' => 1,
  'name' => 1,
  'description' => 1,
  'category_id' => 1,
  'address' => 1,
  'country' => 1,
  'type' => 1,
  'confirmed' => 1,
  'date' => 1,
);
public static $mixin_classes = array (
);

}
