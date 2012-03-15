<?php
namespace LazyRecord\Schema;

class MixinSchemaDeclare extends SchemaDeclare
{
    /* is_a can not work on an abstract class */
    function schema() { }
}
