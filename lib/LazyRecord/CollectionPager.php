<?php
namespace LazyRecord;

interface PagerInterface 
{
    public function setTotal( $total );
    public function setPerPage( $num );
    public function setPage( $num );
    public function calculate();
}

/* copied from include/class.pager.php */
class SimplePager 
    implements PagerInterface 
{

    /* current page number, start from 1 */
    public $currentPage;

    /* size of per page */
    public $perPage;

    /* size of items */
    public $totalItems;

    /* size of pages */
    public $totalPages;

    function __construct( $page = 1 , $pagenum  = 10 )
    {
        if( $page == null )
            $page = 1;

        if( $pagenum == null ) 
            $pagenum = 10;

        $this->perPage     = $pagenum;
        $this->currentPage = $page;
    }

    function setTotal( $total )
    {
        $this->totalItems  = $total;
        $this->calculate();
    }

    function setPerPage( $num ) 
    {
        $this->perPage = $num; 
    }

    function setPage( $num )
    {
        $this->currentPage = $num; 
    }

    function next()
    {
        $this->currentPage++;
    }

    function calculate() 
    {
        $this->startFrom  = ($this->currentPage - 1) * $this->perPage;
        if( $this->startFrom > $this->totalItems )
            throw new \Exception('Pager position exceed.');

        $this->totalPages = $this->totalItems > 0 
            ?  ceil( $this->totalItems / $this->perPage ) 
            : 1;
    }
}

class CollectionPager extends SimplePager 
{
    var $coll;

    function __construct( $coll , $page = 1 , $perPage = 10 ) {
        $this->coll = $coll;
        parent::__construct( $page , $perPage );
        $this->setTotal( $this->coll->count() );
    }


    /* goto next page */
    function next()
    {
        if( $this->currentPage + 1 <= $this->totalPages ) {
            $this->go( $this->currentPage + 1 );
            return true;
        }
        return false;
    }

    /* goto first page */
    function first()
    {
        $this->go(1);
    }

    /* goto last page */
    function last()
    {
        $this->go( $this->totalPages );
    }

    /* goto (page) */
    function go($page)
    {
        $this->setPage( $page );
        $this->calculate();
        $this->seek();
    }

    function seek()
    {
        $this->coll->result->data_seek( $this->startFrom );
    }

    /* return data objects */
    function items()
    {
        $result = $this->coll->result;
        $cnt = 0;
        $items = array();
        $this->seek();
        while( $item = $result->fetch_object( $this->coll->modelClass ) ) {
            if( $cnt++ >= $this->perPage ) 
                break;
            $items[] = $item;
        }
        return $items;
    }
}

?>
