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

		$generator = new \Lazy\Schema\SchemaGenerator;
		$generator->setLogger( $logger );

        $args = func_get_args();
        if( count($args) ) {
            foreach( $args as $path ) {
                $logger->info("Adding schema path $path");
                $generator->addPath( $path );
            }
        } else {
            // default config file.
            $configFile = 'config/lazy.php';
            if( $options->config )
                $configFile = $options->config->value;

            if( file_exists($configFile) ) {
                $loader = new \Lazy\ConfigLoader;
                $loader->load( $configFile );
                foreach( $loader->getSchemaPaths() as $path ) {
                    $logger->info("Adding schema path $path");
                    $generator->addPath( $path );
                }
            }
            else {
                die('Please specify a schema file path or with --config option.');
            }
        }

        $classMap = $generator->generate();

        $logger->info('Classmap:');
		foreach( $classMap as $class => $file ) {
            $path = $file;
            if( strpos( $path , getcwd() ) === 0 )
                $path = substr( $path , strlen(getcwd()) + 1 );
            $logger->info(sprintf("%-32s",$class) . " => $path",1);
		}
        $logger->info('Done');
    }
}

