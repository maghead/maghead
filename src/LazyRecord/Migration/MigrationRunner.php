<?php
namespace LazyRecord\Migration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use LazyRecord\Console;

class MigrationRunner
{


    public $logger;

    public $dataSourceIds = array();

    public function __construct($dsIds)
    {
        $this->logger = Console::getInstance()->getLogger();
        $this->dataSourceIds = $dsIds;
    }

    public function addDataSource( $dsId ) 
    {
        $this->dataSourceIds[] = $dsId;
    }

    public function load($directory) 
    {
        $loaded = array();
        $iterator = new RecursiveIteratorIterator( 
            new RecursiveDirectoryIterator($directory) , RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach( $iterator as $path ) {
            if($path->isFile() && $path->getExtension() === 'php' ) {
                $code = file_get_contents($path);
                if( preg_match('#Migration#',$code) ) {
                    require_once($path);
                    $loaded[] = $path;
                }
            }
        }
        return $loaded;
    }

    public function getMigrationScripts() {
        $classes = get_declared_classes();
        return array_filter($classes, function($class) { 
            return is_a($class,'LazyRecord\\Migration\\Migration',true);
        });
    }

    public function runDowngrade()
    {
        $scripts = $this->getMigrationScripts();
        foreach( $scripts as $script ) {
            foreach( $this->dataSourceIds as $dsId ) {
                $migration = new $script( $dsId );
                $migration->downgrade();
            }
        }
    }

    public function runUpgrade()
    {
        $scripts = $this->getMigrationScripts();
        foreach( $scripts as $script ) {
            foreach( $this->dataSourceIds as $dsId ) {
                $this->logger->info("Running migration script $script on $dsId");
                $migration = new $script( $dsId );
                $migration->upgrade();
            }
        }
    }

}


