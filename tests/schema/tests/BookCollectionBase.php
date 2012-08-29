<?php
namespace tests;



class BookCollectionBase 
extends \LazyRecord\BaseCollection
{

const schema_proxy_class = '\\tests\\BookSchemaProxy';
const model_class = '\\tests\\Book';
const table = 'books';

}
