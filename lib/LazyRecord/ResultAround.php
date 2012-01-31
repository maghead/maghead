<?php

namespace LazyRecord;

class ResultAround 
{
	public $prev;
	public $next;
	public $first;
	public $current;
	public $last;

	function __construct( $result , $current_id = 0 )
	{
        # $result = $dbc->query( "select * from news where lang = '%s' order by id desc" , $lang );
		$result->data_seek( 0 );
        $nums = $result->num_rows;
        $cnt = 0;
        while( $row = $result->fetch_object() ) {
            if( $cnt == 0 ) {
                $this->first = $row;
            }
            if( $row->id == $current_id ) {
                $this->current = $row;
                if( $cnt > 0 ) {
                    $result->data_seek( $cnt-1 );
                    $this->prev    = $result->fetch_object();
                    $result->fetch_object();
                }
                $this->next    = $result->fetch_object();
				break;
            }
            $cnt++;
        }
		if( $nums > 1 ) {
			$result->data_seek( $nums - 1 );
			$this->last = $result->fetch_object();
		}
	}
}


?>
