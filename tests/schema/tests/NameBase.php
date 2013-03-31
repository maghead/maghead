<?php
namespace tests;

class NameBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = 'tests\\NameSchemaProxy';
const collection_class = 'tests\\NameCollection';
const model_class = 'tests\\Name';
const table = 'names';

}
