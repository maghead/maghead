<?php
namespace tests;

class UserCollectionBase  extends \LazyRecord\BaseCollection {
const schema_proxy_class = '\\tests\\UserSchemaProxy';
const model_class = '\\tests\\User';
const table = 'users';


}
