<?php
namespace LazyRecord\Command;
use Exception;
use LazyRecord\ConfigLoader;
use ConfigKit\ConfigCompiler;

function cross_symlink($sourcePath, $targetPath) {
    if ( PHP_OS == "WINNT" ) {
        return link( $sourcePath, $targetPath );
    } else {
        return symlink( $sourcePath, $targetPath );
    }
}

class BuildConfCommand extends \CLIFramework\Command
{

    public function brief()
    {
        return 'Build configuration file.';
    }

    public function arguments($args) {
        $args->add('file')
            ->isa('file')
            ->glob('*.yml')
            ;
    }

    public function execute($configFile = null)
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */
        if( ! $configFile ) {
            if( file_exists( 'db/config/site_database.yml' ) ) {
                $configFile = 'db/config/site_database.yml';
                ConfigLoader::compile($configFile);
            }
            if( file_exists( 'db/config/database.yml' ) ) {
                $configFile = 'db/config/database.yml';
                ConfigLoader::compile($configFile);
            }

            // old config file path.
            if( file_exists( 'config/database.yml' ) ) {
                $configFile = 'config/database.yml';
                ConfigLoader::compile($configFile);
            }
            if( file_exists( 'config/site_database.yml' ) ) {
                $configFile = 'config/site_database.yml';
                ConfigLoader::compile($configFile);
            }
        }
        if( ! $configFile ) {
            throw new Exception("config file path is required.");
        }

        $this->logger->info("Building config from $configFile");
        $dir = dirname($configFile);
        ConfigLoader::compile($configFile);

        // make master config link
        $loader = ConfigLoader::getInstance();

        if ( file_exists( $loader->symbolFilename ) ) {
            unlink( $loader->symbolFilename );
        }
        if ( file_exists('.lazy.php') ) {
            unlink( '.lazy.php' );
        }

        $this->logger->info("Making link => " . $loader->symbolFilename );
        if ( cross_symlink( $configFile , $loader->symbolFilename ) === false ) {
            $this->logger->error("Config linking failed.");
        }
        $this->logger->info("Done.");
    }


}


