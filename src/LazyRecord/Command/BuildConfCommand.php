<?php
namespace LazyRecord\Command;
use Exception;

class BuildConfCommand extends \CLIFramework\Command
{

    public function options($opts)
    {
        $opts->add('output:','output file.');
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

        if( empty($configFiles) )
            throw new Exception("config file path is required.");

        $mainConfigFile = array_shift($configFiles);

        $builder = new \LazyRecord\ConfigBuilder;
        $builder->read( $mainConfigFile );

        foreach( $configFiles as $file ) {
            $builder->merge( $file );
        }

        $builder->validate();
        $content = $builder->build();

        $outputPath = $options->output ? $options->output->value : 'build/lazy/config.php';
        $outputDir  = dirname($outputPath);

        if( ! file_exists($outputDir) )
            mkdir( $outputDir, 0755, true );

        if( file_put_contents( $outputPath , $content ) !== false ) {
            $this->getLogger()->info("Config file is generated at: $outputPath");
        }

    }
}
