<?php

namespace Maghead;

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

    public function __construct(array $dataArray, $page = 1, $pagenum = 10)
    {
        if ($page == null) {
            $page = 1;
        }

        if ($pagenum == null) {
            $pagenum = 10;
        }

        $this->perPage = $pagenum;
        $this->currentPage = $page;
        $this->dataArray = $dataArray;
        $this->calculate();
    }

    public function setPerPage(int $num)
    {
        $this->perPage = $num;
        $this->calculate();
    }

    public function setPage(int $num)
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
        ++$this->currentPage;
        $this->calculate();
    }

    public function previous()
    {
        --$this->currentPage;
        $this->calculate();
    }

    public function hasPreviousPage()
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage()
    {
        return $this->currentPage < $this->totalPages;
    }

    public function getNextPage()
    {
        if ($this->hasNextPage()) {
            return $this->currentPage + 1;
        }
    }

    public function getPreviousPage()
    {
        if ($this->hasPreviousPage()) {
            return $this->currentPage - 1;
        }
    }

    public function calculate()
    {
        $this->startFrom = $this->getOffset();
        $this->totalPages = ($c = count($this->dataArray)) > 0
            ? ceil($c / $this->perPage)
            : 1;
    }

    public function items()
    {
        return array_slice($this->dataArray,
            $this->startFrom,
            $this->perPage
        );
    }

    public function getOffset()
    {
        return ($this->currentPage - 1) * $this->perPage;
    }
}
