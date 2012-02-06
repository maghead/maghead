<?php
namespace Lazy;

class CollectionPager 
{

    /* current page number, start from 1 */
    public $currentPage;

    /* size of per page */
    public $perPage;

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
        $this->calculate();
    }

    public function setPerPage( $num ) 
    {
        $this->perPage = $num; 
        $this->calculate();
    }

    public function setPage( $num )
    {
        $this->currentPage = $num; 
        $this->calculate();
    }

    public function pages()
    {
        return $this->totalPages;
    }

    public function next()
    {
        $this->currentPage++;
        $this->calculate();
    }

    public function previous()
    {
        $this->currentPage--;
        $this->calculate();
    }

    public function calculate() 
    {
        $this->startFrom  = ($this->currentPage - 1) * $this->perPage;
        $this->totalPages = ($c = count($this->dataArray)) > 0 
            ? ceil( $c / $this->perPage ) 
            : 1;
    }

    public function items()
    {
        return array_slice($this->dataArray, 
            $this->startFrom, 
            $this->perPage
        );
    }

}





