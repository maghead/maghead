<?php
namespace tests;



class WineCollectionBase 
extends \LazyRecord\BaseCollection
{

const schema_proxy_class = '\\tests\\WineSchemaProxy';
const model_class = '\\tests\\Wine';
const table = 'wines';

}
