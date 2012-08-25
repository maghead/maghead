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

    function build()
    {
        $ser = new Serializer('php');
        ConfigCompiler::load($configFile);
        return "<?php \n" . $ser->encode($this->config) . "\n?>";
    }
}
