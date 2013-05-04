<?php
namespace tests;

class UserBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\UserSchemaProxy';
const collection_class = 'tests\\UserCollection';
const model_class = 'tests\\User';
const table = 'users';

public static $column_names = array (
  0 => 'account',
  1 => 'password',
  2 => 'id',
);
public static $column_hash = array (
  'account' => 1,
  'password' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
