<?php
namespace tests;

class BookBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\BookSchemaProxy';
const collection_class = 'tests\\BookCollection';
const model_class = 'tests\\Book';
const table = 'books';

public static $column_names = array (
  0 => 'title',
  1 => 'subtitle',
  2 => 'isbn',
  3 => 'description',
  4 => 'view',
  5 => 'publisher_id',
  6 => 'published_at',
  7 => 'created_by',
  8 => 'id',
);
public static $column_hash = array (
  'title' => 1,
  'subtitle' => 1,
  'isbn' => 1,
  'description' => 1,
  'view' => 1,
  'publisher_id' => 1,
  'published_at' => 1,
  'created_by' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
