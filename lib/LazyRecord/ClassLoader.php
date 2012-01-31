<?php
namespace LazyRecord;
/*
    $loader = new ClassLoader;
    $loader->addPath( dirname(__FILE__) . '/model' );
    $loader->register();

    $classLoader = new LazyRecord\ClassLoader;

    // built-in model path
    $modelDir = dirname(dirname(__FILE__)) . "/model";
    if( file_exists( $modelDir ) )
        $classLoader->addModelPath( $modelDir );

    $classLoader->register();
*/
class ClassLoader 
{

    /* model paths */
    public $modelPaths = array();

    function addModelPath( $path ) 
    {
        array_push( $this->modelPaths , $path );
    }

    function loadClass( $className ) 
    {
        $basepath = dirname(dirname( __FILE__ ));
        $className = str_replace( '\\' , DIRECTORY_SEPARATOR , $className );
        $fn = $basepath . DIRECTORY_SEPARATOR . $className . ".php";

        if ( file_exists( $fn ) ) {
            require( $fn );
            return true;
        }
        return false;
    }

    function loadModel( $className ) {
        $fn = $className . ".php";
        foreach( $this->modelPaths as $path ) {
            $fnPath = $path . DIRECTORY_SEPARATOR . $fn;
            if( file_exists( $fnPath ) ) {
                require_once( $fnPath );
                return true;
            }
        }
        return false;
    }

    function autoload($className) {
        $className = ltrim( $className , '\\' );
        if( strpos( $className , "LazyRecord" ) === 0 )
            return $this->loadClass( $className );
        // return $this->loadModel( $className );
    }

    function register() {
        # spl_autoload_register( "LazyRecord\\Loader::loadClass");
        spl_autoload_register( array( $this, "autoload" ) );
    }
}


?>
