<?php
namespace Lazy\Command;


/**
 *
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends \CLIFramework\Command
{

    public function brief()
    {
        return 'build configuration file.';
    }

    public function options($opts)
    {
        $opts->add('c|config:','config file');
    }

    public function execute()
    {
        $logger = $this->getLogger();
        $options = $this->getOptions();
        $configFile = 'config/lazy.php';


		$generator = new \Lazy\Schema\SchemaGenerator;
		$generator->setLogger( $this->getLogger() );

        $args = func_get_args();
        if( count($args) ) {
            foreach( $args as $path ) {
                $generator->addPath( $path );
            }
        } else {

            if( $options->config )
                $configFile = $options->config->value;

            if( file_exists($configFile) ) {
                $loader = new \Lazy\ConfigLoader;
                $loader->load( $configFile );
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

