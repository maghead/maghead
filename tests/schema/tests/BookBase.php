<?php
namespace tests;

class BookBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\BookSchemaProxy';
const collection_class = 'tests\\BookCollection';
const model_class = 'tests\\Book';
const table = 'books';

}
