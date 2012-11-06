<?php
namespace Todos\Model;

class TodoCollectionBase  extends \LazyRecord\BaseCollection {
const schema_proxy_class = '\\Todos\\Model\\TodoSchemaProxy';
const model_class = '\\Todos\\Model\\Todo';
const table = 'todos';

}
