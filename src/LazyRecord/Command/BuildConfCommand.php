<?php
namespace LazyRecord\Command;
use Exception;
use LazyRecord\ConfigLoader;
use ConfigKit\ConfigCompiler;

class BuildConfCommand extends \CLIFramework\Command
{

    public function brief()
    {
        return 'build configuration file.';
    }

    public function execute($configFile = null)
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */
        $options = $this->options;
        if( ! $configFile ) {
            if( file_exists( 'config/site_database.yml' ) )
                $configFile = 'config/site_database.yml';
            if( file_exists( 'config/database.yml' ) )
                $configFile = 'config/database.yml';
        }
        if( ! $configFile )
            throw new Exception("config file path is required.");

        $dir = dirname($configFile);

        ConfigCompiler::compile($configFile);

        // make master config link
        $loader = ConfigLoader::getInstance();
        $this->logger->info("Making link => " . $loader->symbolFilename );

        if( file_exists( $loader->symbolFilename ) )
            unlink( $loader->symbolFilename );
        symlink( $configFile , $loader->symbolFilename );
        $this->logger->info("Done.");
    }


}


