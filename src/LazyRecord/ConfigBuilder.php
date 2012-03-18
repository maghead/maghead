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

    function validate()
    {
        // validate data_sources
        if( ! isset($this->config['data_sources']) ) 
            throw new Exception('data_sources is not defined.');

        if( isset($this->config['schema']['paths']) ) {
            foreach( $this->config['schema']['paths'] as $path ) {
                if( ! file_exists($path) )
                    throw new Exception( 'schema path: ' . $path . ' does not exist.' );
            }
        }
    }

    function build()
    {
        $ser = new Serializer('php');
        return "<?php \n" . $ser->encode($this->config) . "\n?>";
    }

}
