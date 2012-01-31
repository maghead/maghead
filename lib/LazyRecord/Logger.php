<?php
namespace LazyRecord;


class Logger {
    var $config;

    var $filename;
    var $logdir;
    var $fh;

    // function __construct( $dbname,$logdir = null)  {
    function __construct( $config )  {

        $this->config = $config;

        $logdir = @$config['dir'];
        $logmod = @$config['mod'];
        $filename = @$config['filename'];

        if( ! $logdir ) $logdir = LazyRecord_DIR . "/logs";
        if( ! $logmod ) $logmod = 0666;


        $this->createHandle( $logdir , $filename , $logmod );
    }

    function createHandle( $dir , $filename , $logmod = 0666 ) {
        if( ! $filename )
            $filename = sprintf( "%s.log" , date('Y_m_d') );

        if( ! file_exists( $dir ) ) {
            mkdir( $dir );
            chmod( $dir , 0777 );
        }

        $logpath = sprintf( '%s/%s' , $dir , $filename );

        $this->fh = fopen( $logpath , "a+" ) or die( "Can not open file." );
        chmod( $logpath , $logmod );
    }

    function write( $msg ) {
        if( $this->fh )
            fwrite( $this->fh ,sprintf( "%s :: %s\n", date('c') , $msg ));
    }

    function dieWith( $msg ) {
        if( $this->fh )
            fwrite( $this->fh , $msg );
        die( $msg );
    }

    function close() {
        if( $this->fh ) 
            fclose( $this->fh );
    }
}

?>
