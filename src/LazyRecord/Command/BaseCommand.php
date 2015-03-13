<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\ConfigLoader;
use LazyRecord\Metadata;
use LazyRecord\Utils;
use LazyRecord\Schema\SchemaUtils;
use RuntimeException;

class BaseCommand extends Command
{

    /**
     * @var ConfigLoader
     */
    public $config;

    public function init() {
        parent::init();
        // softly load the config file.
        $this->config = ConfigLoader::getInstance();
        $this->config->loadFromSymbol(true); // force loading
        if ($this->config->isLoaded()) {
            $this->config->initForBuild();
        }
    }

    public function getConfigLoader($required = true) 
    {
        if (!$this->config) {
            $this->config = ConfigLoader::getInstance();
            $this->config->loadFromSymbol(true); // force loading
            if (!$loader->isLoaded() && $required) {
                throw new RuntimeException("ConfigLoader did not loaded any config file. Can't initialize the settings.");
            }
        }
        return $this->config;
    }


    public function options($opts)
    {
        parent::options($opts);
        $self = $this;
        $opts->add('D|data-source:', 'specify data source id')
            ->validValues(function() use($self) {
                return $self->config->getDataSourceIds();
            })
            ;
    }

    public function getCurrentDataSourceId() {
        return $this->options->{'data-source'} ?: 'default';
    }


    public function findSchemasByArguments(array $arguments) 
    {
        return SchemaUtils::findSchemasByArguments($this->getConfigLoader(), $arguments , $this->logger);
    }


}
