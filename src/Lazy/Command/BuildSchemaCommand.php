<?php
namespace Lazy\Command;


/**
 *
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends \CLIFramework\Command
{
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
            // XXX: build from config
        
        }

        $classMap = $generator->generate();
		foreach( $classMap as $class => $file ) {
			// path_ok( $file , $class );
#  			unlink( $file );
		}

        $this->getLogger()->info('Done');
    }
}
