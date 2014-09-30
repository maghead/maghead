<?php
namespace TestApp\Model;

class WineCategory extends WineCategoryBase
{
    public function dataLabel()
    {
        return $this->name;
    }
}
