<?php
namespace tests;



class EdmBase 
extends \LazyRecord\BaseModel
{

            const schema_proxy_class = '\\tests\\EdmSchemaProxy';
        const collection_class = '\\tests\\EdmCollection';
        const model_class = '\\tests\\Edm';
        const table = 'Edm';
        
}
