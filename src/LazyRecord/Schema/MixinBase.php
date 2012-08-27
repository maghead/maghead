<?php
namespace LazyRecord\Schema;



class MixinBase 
extends \LazyRecord\BaseModel
{

const schema_proxy_class = '\\LazyRecord\\Schema\\MixinSchemaProxy';
const collection_class = '\\LazyRecord\\Schema\\MixinCollection';
const model_class = '\\LazyRecord\\Schema\\Mixin';
const table = 'mixins';

}
