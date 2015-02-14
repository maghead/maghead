<?php
namespace TestApp\ModelTrait;

trait EBookTrait
{
    public function getLinks() {
        return array('link1', 'link2');
    }

    public function getStores() {
        return array('store1','store2');
    }
}


