<?php
namespace LazyRecord\Migration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationRunner
{
    public $dataSourceIds = array();

    public function __construct($dsIds)
    {
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
    }

    public function runUpgrade()
    {
        $scripts = $this->getMigrationScripts();
    }


}


