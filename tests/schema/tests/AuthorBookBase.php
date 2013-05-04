<?php
namespace tests;

class AuthorBookBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\AuthorBookSchemaProxy';
const collection_class = 'tests\\AuthorBookCollection';
const model_class = 'tests\\AuthorBook';
const table = 'author_books';

public static $column_names = array (
  0 => 'author_id',
  1 => 'created_on',
  2 => 'book_id',
  3 => 'id',
);
public static $column_hash = array (
  'author_id' => 1,
  'created_on' => 1,
  'book_id' => 1,
  'id' => 1,
);
public static $mixin_classes = array (
);

}
