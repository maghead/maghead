<?php
namespace tests;



class AddressBase 
extends \LazyRecord\BaseModel
{

            const schema_proxy_class = '\\tests\\AddressSchemaProxy';
        const collection_class = '\\tests\\AddressCollection';
        const model_class = '\\tests\\Address';
        const table = 'addresses';
        
}
