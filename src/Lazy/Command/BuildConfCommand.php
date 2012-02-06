<?php
namespace Lazy\Command;
use Exception;

class BuildConfCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        $opts->add('o|output:','output file.');
    }

    public function execute()
    {
        /**
         * $ lazy bulid-conf config/lazy.yml phifty/config/lazy.yml
         * 
         * build/lazy/config.php   # is generated
         */
        $options = $this->getOptions();
        $configFiles = func_get_args();

        if( empty($configFiles) ) {
            if( file_exists( 'config/lazy.yml' ) )
                $configFiles = (array)'config/lazy.yml';
        }


        if( empty($configFiles) )
            throw new Exception("config file path is required.");

        $mainConfigFile = array_shift($configFiles);
        $dir = dirname($mainConfigFile);

        $builder = new \Lazy\ConfigBuilder;
        $builder->read( $mainConfigFile );

        foreach( $configFiles as $file ) {
            $builder->merge( $file );
        }

        $builder->validate();
        $content = $builder->build();

        $outputPath = $options->output ? $options->output->value 
                : $dir . DIRECTORY_SEPARATOR . basename( $mainConfigFile , '.yml' ) . '.php';
        $outputDir  = dirname($outputPath);

        if( ! file_exists($outputDir) )
            mkdir( $outputDir, 0755, true );

        if( file_put_contents( $outputPath , $content ) !== false ) {
            $this->getLogger()->info("Config file is generated at: $outputPath");
        }

    }
}
