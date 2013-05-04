<?php
namespace tests;

class EdmBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\EdmSchemaProxy';
const collection_class = 'tests\\EdmCollection';
const model_class = 'tests\\Edm';
const table = 'Edm';

public static $column_names = array (
  0 => 'edmNo',
  1 => 'edmTitle',
  2 => 'edmStart',
  3 => 'edmEnd',
  4 => 'edmContent',
  5 => 'edmUpdatedOn',
);
public static $column_hash = array (
  'edmNo' => 1,
  'edmTitle' => 1,
  'edmStart' => 1,
  'edmEnd' => 1,
  'edmContent' => 1,
  'edmUpdatedOn' => 1,
);
public static $mixin_classes = array (
);

}
