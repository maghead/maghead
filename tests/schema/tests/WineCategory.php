<?php
namespace tests;

class WineCategory extends WineCategoryBase
{
    public function dataLabel()
    {
        return $this->name;
    }
}
