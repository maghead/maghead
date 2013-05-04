<?php
namespace tests;

class AuthorBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\AuthorSchemaProxy';
const collection_class = 'tests\\AuthorCollection';
const model_class = 'tests\\Author';
const table = 'authors';

public static $column_names = array (
  0 => 'name',
  1 => 'email',
  2 => 'identity',
  3 => 'confirmed',
  4 => 'updated_on',
  5 => 'created_on',
  6 => 'id',
);
public static $column_hash = array (
  'name' => 1,
  'email' => 1,
  'identity' => 1,
  'confirmed' => 1,
  'updated_on' => 1,
  'created_on' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
  0 => 'LazyRecord\\Schema\\Mixin\\MetadataSchema',
);

}
