<?php
namespace tests;

class AddressBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\AddressSchemaProxy';
const collection_class = 'tests\\AddressCollection';
const model_class = 'tests\\Address';
const table = 'addresses';

public static $column_names = array (
  0 => 'author_id',
  1 => 'address',
  2 => 'foo',
  3 => 'id',
);
public static $column_hash = array (
  'author_id' => 1,
  'address' => 1,
  'foo' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
