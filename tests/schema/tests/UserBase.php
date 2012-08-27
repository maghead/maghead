<?php
namespace tests;



class UserBase 
extends \\LazyRecord\BaseModel
{

            const schema_proxy_class = '\\tests\\UserSchemaProxy';
        const collection_class = '\\tests\\UserCollection';
        const model_class = '\\tests\\User';
        const table = 'users';
        
}
