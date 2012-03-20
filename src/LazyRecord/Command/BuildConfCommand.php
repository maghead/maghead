<?php
namespace LazyRecord\Command;
use Exception;
use LazyRecord\ConfigBuilder;
use LazyRecord\ConfigLoader;

class BuildConfCommand extends \CLIFramework\Command
{

    public function brief()
    {
        return 'build configuration file.';
    }

    public function options($opts)
    {
        $opts->add('o|output:','output file.');
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

        $mainConfigFile = $configFile;
        $dir = dirname($mainConfigFile);

        $builder = new ConfigBuilder;
        $builder->read( $mainConfigFile );
        $content = $builder->build(); // php source content

        $outputPath = $options->output ? $options->output->value 
                : $dir . DIRECTORY_SEPARATOR . basename( $mainConfigFile , '.yml' ) . '.php';
        $outputDir  = dirname($outputPath);

        if( ! file_exists($outputDir) )
            mkdir( $outputDir, 0755, true );

        if( file_put_contents( $outputPath , $content ) !== false ) {
            $this->getLogger()->info("Config file is generated at: $outputPath");
        }

        // make master config link
        $loader = ConfigLoader::getInstance();
        $this->getLogger()->info("Making link => " . $loader->symbolFilename );

        if( file_exists( $loader->symbolFilename ) )
            unlink( $loader->symbolFilename );

        symlink( $outputPath, $loader->symbolFilename );
        $this->getLogger()->info("Done.");
    }


}


