<?php
namespace LazyRecord;

class CollectionPager 
{

    /* current page number, start from 1 */
    public $currentPage;

    /* size of per page */
    public $perPage;

    /* size of items */
    public $totalItems;

    /* size of pages */
    public $totalPages;


    /* data array */
    public $dataArray;

    public function __construct( $dataArray, $page = 1 , $pagenum  = 10 )
    {
        if( $page == null )
            $page = 1;

        if( $pagenum == null ) 
            $pagenum = 10;

        $this->perPage     = $pagenum;
        $this->currentPage = $page;
        $this->dataArray   = $dataArray;
    }

    public function setTotal( $total )
    {
        $this->totalItems  = $total;
        $this->calculate();
    }

    public function setPerPage( $num ) 
    {
        $this->perPage = $num; 
    }

    public function setPage( $num )
    {
        $this->currentPage = $num; 
    }

    public function next()
    {
        $this->currentPage++;
    }

    public function previous()
    {
        $this->currentPage--;
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

    public function items()
    {
        return $this->dataArray;
    }

}





