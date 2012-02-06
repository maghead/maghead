<?php
namespace Lazy\Command;


/**
 *
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        $opts->add('c|config:','config file');
    }

    public function execute()
    {
		$generator = new \Lazy\SchemaGenerator;
		$generator->setLogger( $this->getLogger() );

        $args = func_get_args();
        if( count($args) ) {
            foreach( $args as $path ) {
                $generator->addPath( $path );
            }
        } else {
            $options = $this->getOptions();
            if( $options->config ) {
                $loader = new \Lazy\ConfigLoader;
                $loader->load( $options->config->value );
                foreach( $loader->getSchemaPaths() as $path ) {
                    $generator->addPath( $path );
                }
            }
            else {
                die('Please specify a schema file path or with --config option.');
            }
        }

        $classMap = $generator->generate();
		foreach( $classMap as $class => $file ) {
			// path_ok( $file , $class );
#  			unlink( $file );
		}

        $this->getLogger()->info('Done');
    }
}
