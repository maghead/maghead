<?php
namespace tests;



class AuthorBookCollectionBase 
extends \Lazy\BaseCollection
{

            const schema_proxy_class = '\\tests\\AuthorBookSchemaProxy';
        const model_class = '\\tests\\AuthorBook';
        const table = 'author_books';
        
}
