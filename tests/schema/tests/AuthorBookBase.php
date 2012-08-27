<?php
namespace tests;



class AuthorBookBase 
extends \LazyRecord\BaseModel
{

const schema_proxy_class = '\\tests\\AuthorBookSchemaProxy';
const collection_class = '\\tests\\AuthorBookCollection';
const model_class = '\\tests\\AuthorBook';
const table = 'author_books';

}
