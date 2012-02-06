<?php
namespace Lazy\Schema;

class MixinSchemaDeclare extends \Lazy\SchemaDeclare
{
    /* is_a can not work on an abstract class */
    function schema() { }
}
