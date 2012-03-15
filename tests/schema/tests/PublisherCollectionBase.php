<?php
namespace tests;



class PublisherCollectionBase 
extends \LazyRecord\BaseCollection
{

            const schema_proxy_class = '\\tests\\PublisherSchemaProxy';
        const model_class = '\\tests\\Publisher';
        const table = 'publishers';
        
}
