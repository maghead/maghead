<?php
namespace Lazy;
use Exception;

class ConfigBuilder
{
    public $config = array();

    function __construct()
    {
        if( ! extension_loaded('yaml') ) {
            dl('yaml.' . PHP_SHLIB_SUFFIX );
        }
    }

    function read($configFile)
    {
        return $this->config = yaml_parse_file($configFile);
    }

    function merge($configFile)
    {
        return $this->config = array_merge( $this->config, 
                yaml_parse_file($configFile));
    }

    function validate()
    {
        // validate data_sources
        if( ! isset($this->config['data_sources']) ) 
            throw new Exception('data_sources is not defined.');

        if( ! isset($this->config['schema']) ) 
            throw new Exception('schema is not defined.');

        if( ! isset($this->config['schema']['paths']) ) 
            throw new Exception('schema.paths is not defined.');

        foreach( $this->config['schema']['paths'] as $path ) {
            if( ! file_exists($path) )
                throw new Exception( 'schema path: ' . $path . ' does not exist.' );
        }

    }

    function build()
    {
        return '<?php return ' . var_export($this->config,true) . ';';
    }

}
