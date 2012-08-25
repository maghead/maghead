<?php
namespace LazyRecord\Schema;



class MixinCollectionBase 
extends \LazyRecord\BaseCollection
{

            const schema_proxy_class = '\\LazyRecord\\Schema\\MixinSchemaProxy';
        const model_class = '\\LazyRecord\\Schema\\Mixin';
        const table = 'mixins';
        
}
