Model schema
============

How do we define a model:

1. Write a model class with schema and extends it from BaseModel class.
2. Run schema builder to inject static code into the model class.

Used schema methods:

* getDir
* getModelClass
* getBaseModelClass
* getCollectionClass
* getBaseCollectionClass
* getSchemaProxyClass
* getNamespace
* getModelName

Dynamic Schema class

    $schema = new DynamicSchemaDeclare( model_class );

Implementation of the ClassInjector

* find injection block first,
    if block is found, replace the block content
    if block is not found, find the end of the class, inject the content with block mark.

