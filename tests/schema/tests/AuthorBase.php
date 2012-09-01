<?php
namespace tests;

class AuthorBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = '\\tests\\AuthorSchemaProxy';
const collection_class = '\\tests\\AuthorCollection';
const model_class = '\\tests\\Author';
const table = 'authors';

}
