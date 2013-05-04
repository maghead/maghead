<?php
namespace tests;

class PublisherBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\PublisherSchemaProxy';
const collection_class = 'tests\\PublisherCollection';
const model_class = 'tests\\Publisher';
const table = 'publishers';

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
