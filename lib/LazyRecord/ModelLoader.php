<?php

namespace LazyRecord;

/*

    $loader = new ModelLoader
    $loader->addPath( dirname(__FILE__) . '/model' );
    $loader->register();

*/
class ModelLoader {
    static $loader;

    public $paths = array();

    function addPath( $path ) {
        array_push( $this->paths , $path );
    }

    function loadModel( $className ) {
        $fn = $className . ".php";
        foreach( $this->paths as $path ) {
            $fnPath = $path . DIRECTORY_SEPARATOR . $fn;
            if( file_exists( $fnPath ) ) {
                require_once( $fnPath );
                return true;
            }
        }
        return false;
    }

    function register() {
        # spl_autoload_register( "LazyRecord\\Loader::loadClass");
        spl_autoload_register( array( $this, "loadModel" ) );
    }
}

?>
