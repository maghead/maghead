<?php
namespace LazyRecord;
use Exception;
use SerializerKit\Serializer;
use ConfigKit\ConfigCompiler;

class ConfigBuilder
{
    public $config = array();

    function read($configFile)
    {
        return $this->config = ConfigCompiler::load($configFile);
    }
}
