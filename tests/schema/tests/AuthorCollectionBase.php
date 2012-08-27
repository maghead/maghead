<?php
namespace tests;



class AuthorCollectionBase 
extends \LazyRecord\BaseCollection
{

const schema_proxy_class = '\\tests\\AuthorSchemaProxy';
const model_class = '\\tests\\Author';
const table = 'authors';

}
