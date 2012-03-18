<?php
namespace LazyRecord;
use Exception;
use SerializerKit\Serializer;

class ConfigBuilder
{
    public $config = array();

    function read($configFile)
    {
        $ser = new Serializer('yaml');
        return $this->config = $ser->decode( file_get_contents($configFile) );
    }

    function build()
    {
        $ser = new Serializer('php');
        return "<?php \n" . $ser->encode($this->config) . "\n?>";
    }

}
