<?php
namespace TestApp;

class WineCategory extends WineCategoryBase
{
    public function dataLabel()
    {
        return $this->name;
    }
}
