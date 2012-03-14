<?php
namespace Lazy\Serializer;

class YamlSerializer
{
    function encode($data) 
    {
        return yaml_emit($data, YAML_UTF8_ENCODING ); 
    }

    function decode($data) 
    {
        return yaml_parse($data); 
    }
}



