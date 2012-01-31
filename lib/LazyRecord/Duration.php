<?php
namespace LazyRecord;

class Duration {
    public $hours;
    public $mins;
    public $secs;

    function __construct( $hours = 0 , $mins = 0 , $secs = 0 ) {
        if( $mins > 60 )
            throw new Exception("Invalid Minutes");

        if( $secs > 60 )
            throw new Exception("Invalid Seconds");

        $this->hours = (int) $hours;
        $this->mins = (int) $mins;
        $this->secs = (int) $secs;
    }

    static function parse( $str ) {
        if( preg_match('/^(\d+):(\d+)(?::(\d+))?$/' , $str , $reg ) ) {
            if( count($reg) == 4 )
                return new self( $reg[1] , $reg[2], $reg[3] );
            elseif( count($reg) == 3 )
                return new self( $reg[1] , $reg[2] );
        }
        return new self;
    }

    function __toString() {
        return sprintf("%02d:%02d:%02d", $this->hours , $this->mins , $this->secs );
    }
}

?>
