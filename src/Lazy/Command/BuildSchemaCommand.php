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
    }

    public function execute()
    {
        $logger = $this->getLogger();
        $options = $this->getOptions();

        $generator = new \Lazy\Schema\SchemaGenerator;
        $generator->setLogger( $logger );

        $loader = new \Lazy\ConfigLoader;
        $loader->loadConfig();
        $loader->init();

        $args = func_get_args();
        if( count($args) ) {
            foreach( $args as $path ) {
                $logger->info("Adding schema path $path");
                $generator->addPath( $path );
            }
        } else {
            foreach( $loader->getSchemaPaths() as $path ) {
                $logger->info("Adding schema path $path");
                $generator->addPath( $path );
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

