<?php
namespace tests;

class PublisherBase  extends \LazyRecord\BaseModel {
const schema_proxy_class = '\\tests\\PublisherSchemaProxy';
const collection_class = '\\tests\\PublisherCollection';
const model_class = '\\tests\\Publisher';
const table = 'publishers';

}
