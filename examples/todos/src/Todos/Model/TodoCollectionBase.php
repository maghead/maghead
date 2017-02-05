<?php
namespace Todos\Model;

class TodoCollectionBase  extends \Maghead\Runtime\BaseCollection {
const SCHEMA_PROXY_CLASS = '\\Todos\\Model\\TodoSchemaProxy';
const model_class = '\\Todos\\Model\\Todo';
const table = 'todos';

}
