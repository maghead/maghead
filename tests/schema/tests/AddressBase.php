<?php
namespace tests;



class AddressBase 
extends \Lazy\BaseModel
{

            const schema_proxy_class = '\\tests\\AddressSchemaProxy';
        const collection_class = '\\tests\\AddressCollection';
        const model_class = '\\tests\\Address';
        const table = 'addresses';
        
}
